<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordAuthorizationTest extends TestCase
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

    public function test_superadmin_can_change_any_user_password(): void
    {
        $superadminRole = $this->createRole('superadmin');
        $staffRole = $this->createRole('staff');

        $superadmin = $this->createUserWithRole($superadminRole);
        $staff = $this->createUserWithRole($staffRole);
        $originalPasswordHash = $staff->password;

        $response = $this->actingAs($superadmin, 'sanctum')
            ->patchJson("/api/users/{$staff->id}/password", [
                'password' => 'nuevaClave123',
                'password_confirmation' => 'nuevaClave123',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Password updated successfully']);

        $staff->refresh();
        $this->assertNotSame($originalPasswordHash, $staff->password);
        $this->assertTrue(Hash::check('nuevaClave123', $staff->password));
    }

    public function test_admin_can_change_password_of_own_staff_only(): void
    {
        $superadminRole = $this->createRole('superadmin');
        $adminRole = $this->createRole('admin');
        $staffRole = $this->createRole('staff');
        $clienteRole = $this->createRole('cliente', false);

        $admin = $this->createUserWithRole($adminRole);
        $otherAdmin = $this->createUserWithRole($adminRole);
        $ownStaff = $this->createUserWithRole($staffRole, ['created_by' => $admin->id]);
        $foreignStaff = $this->createUserWithRole($staffRole, ['created_by' => $otherAdmin->id]);
        $cliente = $this->createUserWithRole($clienteRole, ['created_by' => $admin->id]);
        $superadmin = $this->createUserWithRole($superadminRole);

        $allowed = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$ownStaff->id}/password", [
                'password' => 'staffNueva123',
                'password_confirmation' => 'staffNueva123',
            ]);

        $allowed->assertStatus(200);

        $forbiddenForeign = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$foreignStaff->id}/password", [
                'password' => 'x1234567',
                'password_confirmation' => 'x1234567',
            ]);

        $forbiddenForeign->assertStatus(403)
            ->assertJsonFragment(['message' => 'Admin can only change passwords of their own staff users']);

        $forbiddenCliente = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$cliente->id}/password", [
                'password' => 'x1234567',
                'password_confirmation' => 'x1234567',
            ]);

        $forbiddenCliente->assertStatus(403)
            ->assertJsonFragment(['message' => 'Admin can only change passwords of their own staff users']);

        // sanity check: superadmin still can update any
        $superAllowed = $this->actingAs($superadmin, 'sanctum')
            ->patchJson("/api/users/{$cliente->id}/password", [
                'password' => 'clienteNueva123',
                'password_confirmation' => 'clienteNueva123',
            ]);

        $superAllowed->assertStatus(200);
    }
}
