<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentCashImmediateTest extends TestCase
{
    use RefreshDatabase;

    private function createRole(string $name, bool $isGlobal = true): Role
    {
        return Role::create([
            'name' => $name,
            'is_global' => $isGlobal,
        ]);
    }

    public function test_cash_payment_immediate_updates_pending_payment_and_marks_order_paid(): void
    {
        $adminRole = $this->createRole('admin');
        $user = User::factory()->create(['role_id' => $adminRole->id]);

        $restaurant = Restaurant::factory()->create();

        DB::table('user_restaurant')->insert([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $adminRole->id,
        ]);

        $order = Order::create([
            'restaurant_id' => $restaurant->id,
            'user_id' => null,
            'table_id' => null,
            'type' => 'local',
            'status' => 'pending',
            'total' => 50,
            'notes' => null,
        ]);

        $pendingPayment = Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'provider' => 'none',
            'provider_payment_id' => null,
            'amount' => 50,
            'currency' => 'eur',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/payments/cash", [
                'amount' => 50,
                'currency' => 'eur',
                'immediate' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Cash payment created successfully',
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $pendingPayment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);

        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
    }
}
