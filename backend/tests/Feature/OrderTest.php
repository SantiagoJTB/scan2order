<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_index_success()
    {
        Order::factory()->count(2)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/orders');
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_index_unauthorized()
    {
        $response = $this->getJson('/api/orders');
        $response->assertStatus(401);
    }

    public function test_store_success()
    {
        $user = User::factory()->create();
        $rest = Restaurant::factory()->create();
        $payload = [
            'restaurant_id' => $rest->id,
            'type' => 'local',
            'status' => 'pending',
            'total' => 0,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', $payload);
        $response->assertStatus(201)
                 ->assertJsonFragment(['restaurant_id' => $rest->id]);
    }

    public function test_store_validation_error()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/orders', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['restaurant_id', 'type', 'status']);
    }

    public function test_show_success()
    {
        $order = Order::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/orders/{$order->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $order->id]);
    }
}
