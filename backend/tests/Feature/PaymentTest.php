<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_make_payment_success()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $payload = [
            'method' => 'cash',
            'amount' => 50,
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson("/api/orders/{$order->id}/payments", $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['order_id' => $order->id]);
    }

    public function test_make_payment_validation_error()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson("/api/orders/{$order->id}/payments", []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['method', 'amount']);
    }

    public function test_make_payment_unauthorized()
    {
        $order = Order::factory()->create();

        $response = $this->postJson("/api/orders/{$order->id}/payments", []);
        $response->assertStatus(401);
    }
}
