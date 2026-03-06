<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_index_success()
    {
        Product::factory()->count(2)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/products');
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_index_unauthorized()
    {
        $response = $this->getJson('/api/products');
        $response->assertStatus(401);
    }

    public function test_store_success()
    {
        $user = User::factory()->create();
        $rest = Restaurant::factory()->create();
        $payload = [
            'restaurant_id' => $rest->id,
            'name' => 'Item',
            'price' => 10,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', $payload);
        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Item']);
    }

    public function test_store_validation_error()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['restaurant_id', 'name', 'price']);
    }

    public function test_update_success()
    {
        $product = Product::factory()->create(['name' => 'Old']);
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/products/{$product->id}", [
            'name' => 'New',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'New']);
    }

    public function test_delete_success()
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/products/{$product->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
