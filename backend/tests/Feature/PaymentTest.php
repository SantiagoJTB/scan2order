<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Stripe\Event;
use Stripe\PaymentIntent;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_create_stripe_payment_intent_success()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['total' => 42.50]);

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'client_secret' => 'pi_test_secret_123',
        ]);

        $this->mock(StripePaymentService::class, function (MockInterface $mock) use ($paymentIntent) {
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->andReturn($paymentIntent);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/payments/stripe", [
                'amount' => 42.50,
                'currency' => 'eur',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Stripe payment intent created',
                'client_secret' => 'pi_test_secret_123',
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'stripe',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'currency' => 'eur',
            'status' => 'pending',
        ]);
    }

    public function test_create_stripe_payment_intent_unauthorized()
    {
        $order = Order::factory()->create();

        $response = $this->postJson("/api/orders/{$order->id}/payments/stripe", [
            'amount' => 30,
            'currency' => 'eur',
        ]);

        $response->assertStatus(401);
    }

    public function test_get_payment_details_success()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'stripe',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_show_test',
            'amount' => 12.34,
            'currency' => 'eur',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $payment->id,
                'provider_payment_id' => 'pi_show_test',
                'status' => 'pending',
            ]);
    }

    public function test_stripe_webhook_succeeded_updates_payment_and_order()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'stripe',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_webhook_success',
            'amount' => 20.00,
            'currency' => 'eur',
            'status' => 'pending',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_success_1',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_webhook_success',
                ],
            ],
        ]);

        $this->mock(StripePaymentService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('constructWebhookEvent')
                ->once()
                ->andReturn($event);
        });

        $response = $this->withHeaders([
            'Stripe-Signature' => 'test_signature',
        ])->postJson('/api/webhooks/stripe', ['dummy' => 'payload']);

        $response->assertStatus(200)
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_stripe_webhook_requires_signature_header()
    {
        $response = $this->postJson('/api/webhooks/stripe', ['dummy' => 'payload']);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Missing Stripe signature']);
    }

    public function test_stripe_webhook_payment_failed_updates_payment_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'stripe',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_webhook_failed',
            'amount' => 30.00,
            'currency' => 'eur',
            'status' => 'pending',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_failed_1',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_webhook_failed',
                ],
            ],
        ]);

        $this->mock(StripePaymentService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('constructWebhookEvent')
                ->once()
                ->andReturn($event);
        });

        $response = $this->withHeaders([
            'Stripe-Signature' => 'test_signature',
        ])->postJson('/api/webhooks/stripe', ['dummy' => 'payload']);

        $response->assertStatus(200)
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
    }

    public function test_create_cash_payment_success()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['total' => 50.00]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/payments/cash", [
                'amount' => 50.00,
                'currency' => 'eur',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Cash payment created successfully',
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'cash',
            'provider' => 'none',
            'provider_payment_id' => null,
            'amount' => 50.00,
            'currency' => 'eur',
            'status' => 'pending',
        ]);
    }

    public function test_create_cash_payment_unauthorized()
    {
        $order = Order::factory()->create();

        $response = $this->postJson("/api/orders/{$order->id}/payments/cash", [
            'amount' => 25.00,
            'currency' => 'eur',
        ]);

        $response->assertStatus(401);
    }
}
