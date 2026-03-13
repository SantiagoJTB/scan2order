<?php

namespace Tests\Feature;

use App\Mail\MfaCodeMail;
use App\Models\EmailMfaCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminMfaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_superadmin_login_requires_mfa_when_configured(): void
    {
        Mail::fake();

        $superadminRoleId = Role::where('name', 'superadmin')->value('id');

        $user = User::factory()->create([
            'role_id' => $superadminRoleId,
            'status' => 'active',
            'password' => bcrypt('password123'),
            'mfa_enabled_at' => now(),
        ]);

        $responseWithoutCode = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $responseWithoutCode
            ->assertStatus(403)
            ->assertJson(['mfa_required' => true]);

        $code = null;
        Mail::assertQueued(MfaCodeMail::class, function (MfaCodeMail $mail) use ($user, &$code) {
            $code = $mail->code;
            return $mail->hasTo($user->email);
        });

        $responseInvalidCode = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
            'mfa_code' => '000000',
        ]);

        $responseInvalidCode
            ->assertStatus(403)
            ->assertJson(['mfa_required' => true]);

        $responseValidCode = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
            'mfa_code' => $code,
        ]);

        $responseValidCode
            ->assertStatus(200)
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
    }

    public function test_superadmin_can_setup_and_enable_mfa(): void
    {
        Mail::fake();

        $superadminRoleId = Role::where('name', 'superadmin')->value('id');

        $user = User::factory()->create([
            'role_id' => $superadminRoleId,
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $setupResponse = $this->postJson('/api/mfa/setup');
        $setupResponse
            ->assertStatus(200)
            ->assertJsonFragment(['method' => 'email']);

        Mail::assertQueued(MfaCodeMail::class);

        $record = EmailMfaCode::where('user_id', $user->id)
            ->where('purpose', 'enable')
            ->latest('id')
            ->first();

        $this->assertNotNull($record);

        $mailCode = null;
        Mail::assertQueued(MfaCodeMail::class, function (MfaCodeMail $mail) use (&$mailCode, $user) {
            $mailCode = $mail->code;
            return $mail->hasTo($user->email);
        });

        $enableResponse = $this->postJson('/api/mfa/enable', [
            'mfa_code' => $mailCode,
        ]);

        $enableResponse->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertNotNull($user->fresh()->mfa_enabled_at);
    }

    public function test_non_superadmin_cannot_setup_mfa(): void
    {
        $adminRoleId = Role::where('name', 'admin')->value('id');

        $admin = User::factory()->create([
            'role_id' => $adminRoleId,
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/mfa/setup');
        $response->assertStatus(403);
    }
}
