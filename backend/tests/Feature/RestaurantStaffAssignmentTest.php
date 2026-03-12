<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantStaffAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function createRole(string $name, bool $isGlobal = true): Role
    {
        return Role::create([
            'name' => $name,
            'is_global' => $isGlobal,
        ]);
    }

    private function createUserWithRole(Role $role, array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => $role->id,
        ], $extra));
    }

    public function test_superadmin_cannot_assign_staff_from_other_admin(): void
    {
        $superadminRole = $this->createRole('superadmin');
        $adminRole = $this->createRole('admin');
        $staffRole = $this->createRole('staff');

        $superadmin = $this->createUserWithRole($superadminRole);
        $adminOwner = $this->createUserWithRole($adminRole);
        $otherAdmin = $this->createUserWithRole($adminRole);

        $restaurant = Restaurant::factory()->create(['created_by' => $adminOwner->id]);

        DB::table('user_restaurant')->insert([
            'user_id' => $adminOwner->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $adminRole->id,
        ]);

        $foreignStaff = $this->createUserWithRole($staffRole, ['created_by' => $otherAdmin->id]);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/restaurants/{$restaurant->id}/staffs", [
                'staff_ids' => [$foreignStaff->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Some users are not valid staff for this assignment',
            ]);

        $this->assertDatabaseMissing('user_restaurant', [
            'user_id' => $foreignStaff->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $staffRole->id,
        ]);
    }

    public function test_superadmin_can_assign_staff_from_restaurant_admin(): void
    {
        $superadminRole = $this->createRole('superadmin');
        $adminRole = $this->createRole('admin');
        $staffRole = $this->createRole('staff');

        $superadmin = $this->createUserWithRole($superadminRole);
        $adminOwner = $this->createUserWithRole($adminRole);

        $restaurant = Restaurant::factory()->create(['created_by' => $adminOwner->id]);

        DB::table('user_restaurant')->insert([
            'user_id' => $adminOwner->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $adminRole->id,
        ]);

        $validStaff = $this->createUserWithRole($staffRole, ['created_by' => $adminOwner->id]);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/restaurants/{$restaurant->id}/staffs", [
                'staff_ids' => [$validStaff->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Restaurant staffs updated',
            ]);

        $this->assertDatabaseHas('user_restaurant', [
            'user_id' => $validStaff->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $staffRole->id,
        ]);
    }

    public function test_superadmin_can_assign_admin_without_staff(): void
    {
        $superadminRole = $this->createRole('superadmin');
        $adminRole = $this->createRole('admin');
        $staffRole = $this->createRole('staff');

        $superadmin = $this->createUserWithRole($superadminRole);
        $adminOwner = $this->createUserWithRole($adminRole);

        $restaurant = Restaurant::factory()->create(['created_by' => $adminOwner->id]);

        DB::table('user_restaurant')->insert([
            'user_id' => $adminOwner->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $adminRole->id,
        ]);

        $existingStaff = $this->createUserWithRole($staffRole, ['created_by' => $adminOwner->id]);

        DB::table('user_restaurant')->insert([
            'user_id' => $existingStaff->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $staffRole->id,
        ]);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->putJson("/api/restaurants/{$restaurant->id}/staffs", [
                'staff_ids' => [],
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Restaurant staffs updated',
            ]);

        $this->assertDatabaseMissing('user_restaurant', [
            'user_id' => $existingStaff->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $staffRole->id,
        ]);

        $this->assertDatabaseHas('user_restaurant', [
            'user_id' => $adminOwner->id,
            'restaurant_id' => $restaurant->id,
            'role_id' => $adminRole->id,
        ]);
    }
}
