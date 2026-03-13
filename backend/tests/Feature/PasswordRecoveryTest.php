<?php

namespace Tests\Feature;

use App\Mail\PasswordResetTokenMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_forgot_password_sends_reset_token_email(): void
    {
        Mail::fake();

        $clienteRoleId = Role::where('name', 'cliente')->value('id');
        $user = User::factory()->create([
            'role_id' => $clienteRoleId,
            'email' => 'recover@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        Mail::assertQueued(PasswordResetTokenMail::class, function (PasswordResetTokenMail $mail) use ($user) {
            return $mail->hasTo($user->email) && !empty($mail->token);
        });
    }

    public function test_user_can_reset_password_with_token_received_by_email(): void
    {
        Mail::fake();

        $clienteRoleId = Role::where('name', 'cliente')->value('id');
        $user = User::factory()->create([
            'role_id' => $clienteRoleId,
            'email' => 'reset@example.com',
            'password' => bcrypt('oldPassword1'),
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ])->assertStatus(200);

        $token = null;
        Mail::assertQueued(PasswordResetTokenMail::class, function (PasswordResetTokenMail $mail) use (&$token, $user) {
            if (!$mail->hasTo($user->email)) {
                return false;
            }

            $token = $mail->token;
            return true;
        });

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Password reset successfully']);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'NewPassword123',
        ]);

        $loginResponse->assertStatus(200);
    }
}
