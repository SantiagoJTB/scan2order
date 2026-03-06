<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_add_item_success()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();
        $product = Product::factory()->create();

        $payload = [
            'product_id' => $product->id,
            'quantity' => 2,
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson("/api/orders/{$order->id}/items", $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['order_id' => $order->id]);
    }

    public function test_add_item_validation_error()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson("/api/orders/{$order->id}/items", []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['product_id', 'quantity']);
    }

    public function test_add_item_unauthorized()
    {
        $order = Order::factory()->create();
        $response = $this->postJson("/api/orders/{$order->id}/items", []);
        $response->assertStatus(401);
    }

    public function test_index_items_success()
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson("/api/orders/{$order->id}/items");

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }
}
