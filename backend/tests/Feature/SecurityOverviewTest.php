<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function createRole(string $name): Role
    {
        return Role::create([
            'name' => $name,
            'is_global' => true,
        ]);
    }

    public function test_superadmin_can_view_security_overview(): void
    {
        $superadminRole = $this->createRole('superadmin');
        $adminRole = $this->createRole('admin');

        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'status' => 'active',
            'mfa_enabled_at' => now(),
        ]);

        $target = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        AuditLog::create([
            'actor_user_id' => $superadmin->id,
            'target_user_id' => $target->id,
            'action' => 'user.password_update',
            'resource_type' => 'user',
            'resource_id' => (string) $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => ['source' => 'test'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->getJson('/api/admin/security/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'active_superadmins',
                    'superadmins_with_mfa',
                    'events_last_24h',
                    'high_risk_actions_last_24h',
                ],
                'recent_events' => [[
                    'id',
                    'action',
                    'resource_type',
                    'resource_id',
                    'actor',
                    'target',
                    'ip_address',
                    'created_at',
                ]],
            ])
            ->assertJsonPath('summary.active_superadmins', 1)
            ->assertJsonPath('summary.superadmins_with_mfa', 1);
    }

    public function test_non_superadmin_cannot_view_security_overview(): void
    {
        $adminRole = $this->createRole('admin');

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/security/overview');

        $response->assertStatus(403);
    }

    public function test_superadmin_can_filter_security_events_by_action_and_date_range(): void
    {
        $superadminRole = $this->createRole('superadmin');

        $superadmin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'status' => 'active',
        ]);

        AuditLog::create([
            'actor_user_id' => $superadmin->id,
            'target_user_id' => null,
            'action' => 'user.delete',
            'resource_type' => 'user',
            'resource_id' => '10',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => [],
            'created_at' => now()->subDays(2),
        ]);

        AuditLog::create([
            'actor_user_id' => $superadmin->id,
            'target_user_id' => null,
            'action' => 'user.password_update',
            'resource_type' => 'user',
            'resource_id' => '11',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'metadata' => [],
            'created_at' => now(),
        ]);

        $from = now()->subDay()->toDateString();
        $to = now()->toDateString();

        $response = $this->actingAs($superadmin, 'sanctum')
            ->getJson("/api/admin/security/overview?action=user.password_update&from={$from}&to={$to}");

        $response->assertStatus(200)
            ->assertJsonPath('applied_filters.action', 'user.password_update')
            ->assertJsonPath('applied_filters.from', $from)
            ->assertJsonPath('applied_filters.to', $to)
            ->assertJsonCount(1, 'recent_events')
            ->assertJsonPath('recent_events.0.action', 'user.password_update');
    }
}
