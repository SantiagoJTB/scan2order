<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_index_success()
    {
        Restaurant::factory()->count(3)->create();

        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/restaurants');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_index_unauthorized()
    {
        $response = $this->getJson('/api/restaurants');
        $response->assertStatus(401);
    }

    public function test_store_success()
    {
        $user = User::factory()->create();
        $payload = ['name' => 'My Resto', 'address' => 'Addr'];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/restaurants', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'My Resto']);
        $this->assertDatabaseHas('restaurants', ['name' => 'My Resto']);
    }

    public function test_store_validation_error()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/restaurants', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_show_success()
    {
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/restaurants/{$restaurant->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $restaurant->id]);
    }
}
