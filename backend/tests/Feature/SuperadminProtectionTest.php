<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminProtectionTest extends TestCase
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
            'status' => 'active',
        ], $extra));
    }

    public function test_cannot_deactivate_last_active_superadmin(): void
    {
        $superadminRole = $this->createRole('superadmin');

        $superadmin = $this->createUserWithRole($superadminRole);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->patchJson("/api/users/{$superadmin->id}/status", [
                'status' => 'inactive',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Cannot deactivate the last active superadmin',
            ]);
    }

    public function test_can_deactivate_superadmin_when_another_active_exists(): void
    {
        $superadminRole = $this->createRole('superadmin');

        $actingSuperadmin = $this->createUserWithRole($superadminRole);
        $targetSuperadmin = $this->createUserWithRole($superadminRole);

        $response = $this->actingAs($actingSuperadmin, 'sanctum')
            ->patchJson("/api/users/{$targetSuperadmin->id}/status", [
                'status' => 'inactive',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'User status updated']);
    }

    public function test_cannot_delete_last_active_superadmin(): void
    {
        $superadminRole = $this->createRole('superadmin');

        $superadmin = $this->createUserWithRole($superadminRole);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->deleteJson("/api/users/{$superadmin->id}");

        // Own-delete is blocked first by self-delete rule
        $response->assertStatus(422);

        // Verify protection specifically with second actor attempt
        $anotherSuperadmin = $this->createUserWithRole($superadminRole, ['status' => 'inactive']);

        $responseByOther = $this->actingAs($anotherSuperadmin, 'sanctum')
            ->deleteJson("/api/users/{$superadmin->id}");

        $responseByOther->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Cannot delete the last active superadmin',
            ]);
    }
}
