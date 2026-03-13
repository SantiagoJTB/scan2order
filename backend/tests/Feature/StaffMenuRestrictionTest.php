<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffMenuRestrictionTest extends TestCase
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

    public function test_staff_can_only_edit_menu_of_assigned_restaurant(): void
    {
        $adminRole = $this->createRole('admin');
        $staffRole = $this->createRole('staff');

        $admin = $this->createUserWithRole($adminRole);
        $staff = $this->createUserWithRole($staffRole, ['created_by' => $admin->id]);

        $assignedRestaurant = Restaurant::factory()->create(['created_by' => $admin->id]);
        $otherRestaurant = Restaurant::factory()->create(['created_by' => $admin->id]);

        DB::table('user_restaurant')->insert([
            [
                'user_id' => $admin->id,
                'restaurant_id' => $assignedRestaurant->id,
                'role_id' => $adminRole->id,
            ],
            [
                'user_id' => $admin->id,
                'restaurant_id' => $otherRestaurant->id,
                'role_id' => $adminRole->id,
            ],
            [
                'user_id' => $staff->id,
                'restaurant_id' => $assignedRestaurant->id,
                'role_id' => $staffRole->id,
            ],
        ]);

        $assignedCatalog = Catalog::create([
            'restaurant_id' => $assignedRestaurant->id,
            'name' => 'Catálogo asignado',
            'active' => true,
            'order' => 1,
        ]);

        $otherCatalog = Catalog::create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Catálogo no asignado',
            'active' => true,
            'order' => 1,
        ]);

        // Staff can update assigned restaurant menu
        $allowedResponse = $this->actingAs($staff, 'sanctum')
            ->putJson("/api/restaurants/{$assignedRestaurant->id}/catalogs/{$assignedCatalog->id}", [
                'name' => 'Catálogo asignado actualizado',
            ]);

        $allowedResponse->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Catálogo asignado actualizado',
            ]);

        // Staff cannot update menu from other restaurant (even if same admin owns both)
        $forbiddenResponse = $this->actingAs($staff, 'sanctum')
            ->putJson("/api/restaurants/{$otherRestaurant->id}/catalogs/{$otherCatalog->id}", [
                'name' => 'Intento no permitido',
            ]);

        $forbiddenResponse->assertStatus(403);

        $this->assertDatabaseHas('catalogs', [
            'id' => $otherCatalog->id,
            'name' => 'Catálogo no asignado',
        ]);
    }

    public function test_staff_stats_only_include_assigned_restaurant(): void
    {
        $adminRole = $this->createRole('admin');
        $staffRole = $this->createRole('staff');

        $admin = $this->createUserWithRole($adminRole);
        $staff = $this->createUserWithRole($staffRole, ['created_by' => $admin->id]);

        $assignedRestaurant = Restaurant::factory()->create(['created_by' => $admin->id, 'name' => 'Asignado']);
        $otherRestaurant = Restaurant::factory()->create(['created_by' => $admin->id, 'name' => 'No asignado']);

        DB::table('user_restaurant')->insert([
            [
                'user_id' => $admin->id,
                'restaurant_id' => $assignedRestaurant->id,
                'role_id' => $adminRole->id,
            ],
            [
                'user_id' => $admin->id,
                'restaurant_id' => $otherRestaurant->id,
                'role_id' => $adminRole->id,
            ],
            [
                'user_id' => $staff->id,
                'restaurant_id' => $assignedRestaurant->id,
                'role_id' => $staffRole->id,
            ],
        ]);

        Catalog::create([
            'restaurant_id' => $assignedRestaurant->id,
            'name' => 'Catálogo A',
            'active' => true,
            'order' => 1,
        ]);

        Catalog::create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Catálogo B',
            'active' => true,
            'order' => 1,
        ]);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/restaurants/stats');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Asignado'])
            ->assertJsonMissing(['name' => 'No asignado']);
    }
}
