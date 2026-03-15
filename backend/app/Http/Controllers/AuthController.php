<?php

namespace App\Http\Controllers;

use App\Mail\MfaCodeMail;
use App\Mail\PasswordResetTokenMail;
use App\Models\EmailMfaCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Get cliente role by name (more robust than hardcoding ID)
        $clienteRole = \App\Models\Role::where('name', 'cliente')->first();
        
        if (!$clienteRole) {
            return response()->json(['message' => 'Cliente role not found'], 500);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $clienteRole->id,
            'status' => 'active',
        ]);

            $user->load('role');
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
            'user' => $this->userWithRole($user),
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'mfa_code' => 'nullable|string',
        ]);

        if (!Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
            $user->load('role');
        
        // Verificar estado
        if ($user->status !== 'active') {
            return response()->json(['message' => 'User account is not active'], 403);
        }

        if ($user->role?->name === 'superadmin' && !empty($user->mfa_enabled_at)) {
            $mfaCode = (string) ($credentials['mfa_code'] ?? '');

            if ($mfaCode === '') {
                $this->sendEmailMfaCode($user, 'login');
                Auth::logout();

                return response()->json([
                    'message' => 'MFA code is required for superadmin. A code was sent to your email.',
                    'mfa_required' => true,
                ], 403);
            }

            if (!$this->consumeEmailMfaCode($user, 'login', $mfaCode)) {
                Auth::logout();

                return response()->json([
                    'message' => 'Invalid MFA code',
                    'mfa_required' => true,
                ], 403);
            }
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $this->auditAction(
            actor: $user,
            action: 'auth.login',
            resourceType: 'user',
            resourceId: $user->id,
            targetUser: $user,
            metadata: ['role' => $user->role?->name],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        return response()->json([
            'user' => $this->userWithRole($user),
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $user = $request->user();
        $this->auditAction(
            actor: $user,
            action: 'auth.logout',
            resourceType: 'user',
            resourceId: $user?->id,
            targetUser: $user,
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
           $user = $request->user();
           $user->load('role');
           return response()->json($this->userWithRole($user));
    }

    public function mfaSetup(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        if ($user->role?->name !== 'superadmin') {
            return response()->json(['message' => 'Only superadmin can configure MFA'], 403);
        }

        $data = $request->validate([
            'action' => 'nullable|in:enable,disable',
        ]);

        $purpose = ($data['action'] ?? 'enable') === 'disable' ? 'disable' : 'enable';

        $this->sendEmailMfaCode($user, $purpose);

        $this->auditAction(
            actor: $user,
            action: 'auth.mfa_setup',
            resourceType: 'user',
            resourceId: $user->id,
            targetUser: $user,
            metadata: ['purpose' => $purpose],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        return response()->json([
            'message' => 'Verification code sent to your email',
            'method' => 'email',
            'purpose' => $purpose,
        ]);
    }

    public function mfaEnable(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        if ($user->role?->name !== 'superadmin') {
            return response()->json(['message' => 'Only superadmin can enable MFA'], 403);
        }

        $data = $request->validate([
            'mfa_code' => 'required|string',
        ]);

        if (!$this->consumeEmailMfaCode($user, 'enable', $data['mfa_code'])) {
            return response()->json(['message' => 'Invalid MFA code'], 422);
        }

        $user->update([
            'mfa_secret' => null,
            'mfa_enabled_at' => now(),
        ]);

        $this->auditAction(
            actor: $user,
            action: 'auth.mfa_enable',
            resourceType: 'user',
            resourceId: $user->id,
            targetUser: $user,
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        return response()->json(['message' => 'MFA enabled successfully']);
    }

    public function mfaDisable(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        if ($user->role?->name !== 'superadmin') {
            return response()->json(['message' => 'Only superadmin can disable MFA'], 403);
        }

        $data = $request->validate([
            'mfa_code' => 'required|string',
        ]);

        if (!$user->mfa_enabled_at) {
            return response()->json(['message' => 'MFA is not configured'], 422);
        }

        if (!$this->consumeEmailMfaCode($user, 'disable', $data['mfa_code'])) {
            return response()->json(['message' => 'Invalid MFA code'], 422);
        }

        $user->update([
            'mfa_secret' => null,
            'mfa_enabled_at' => null,
        ]);

        $this->auditAction(
            actor: $user,
            action: 'auth.mfa_disable',
            resourceType: 'user',
            resourceId: $user->id,
            targetUser: $user,
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        return response()->json(['message' => 'MFA disabled successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();
        if ($user) {
            $token = Password::broker('users')->createToken($user);
            $minutes = (int) config('auth.passwords.users.expire', 60);

            Mail::to($user->email)->queue(new PasswordResetTokenMail(
                token: $token,
                email: $user->email,
                minutes: $minutes
            ));

            $this->auditAction(
                actor: null,
                action: 'auth.password_reset_requested',
                resourceType: 'user',
                resourceId: $user->id,
                targetUser: $user,
                ipAddress: $request->ip(),
                userAgent: (string) $request->userAgent()
            );
        }

        return response()->json([
            'message' => 'If the email exists, a password reset token was sent.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::broker('users')->reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        $user = User::where('email', $data['email'])->first();
        $this->auditAction(
            actor: null,
            action: 'auth.password_reset_completed',
            resourceType: 'user',
            resourceId: $user?->id,
            targetUser: $user,
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        return response()->json(['message' => 'Password reset successfully']);
    }

    /**
     * Format user with role and permissions.
     * For staff users, include the linked restaurant ID.
     */
    private function userWithRole($user)
    {
        $userArray = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
            ] : null,
            'permissions' => $user->permissions()->pluck('name'),
            'mfa_enabled' => $user->mfa_enabled_at !== null,
        ];

        // For staff users, include the linked restaurant ID
        if ($user->role && $user->role->name === 'staff') {
            // Staff must have exactly one restaurant assigned
            $restaurant = $user->restaurants()->first();

            $userArray['restaurant_id'] = $restaurant?->id ?? null;
            $userArray['restaurant_name'] = $restaurant?->name ?? null;
        }

        return $userArray;
    }

    private function sendEmailMfaCode(User $user, string $purpose): void
    {
        EmailMfaCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ttlMinutes = max(1, (int) config('security.mfa_email_code_ttl_minutes', 10));
        $expiresAt = now()->addMinutes($ttlMinutes);

        EmailMfaCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'attempts' => 0,
        ]);

        $purposeLabel = match ($purpose) {
            'enable' => 'activar MFA',
            'disable' => 'desactivar MFA',
            default => 'iniciar sesión',
        };

        Mail::to($user->email)->queue(new MfaCodeMail(
            code: $code,
            minutes: $ttlMinutes,
            purpose: $purposeLabel
        ));
    }

    private function consumeEmailMfaCode(User $user, string $purpose, string $code): bool
    {
        $normalizedCode = preg_replace('/\D+/', '', $code ?? '');
        if (!$normalizedCode || strlen($normalizedCode) !== 6) {
            return false;
        }

        $entry = EmailMfaCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        $maxAttempts = max(1, (int) config('security.mfa_email_max_attempts', 5));
        if (!$entry || $entry->attempts >= $maxAttempts) {
            return false;
        }

        if (!Hash::check($normalizedCode, $entry->code_hash)) {
            $entry->increment('attempts');
            return false;
        }

        $entry->update([
            'used_at' => now(),
        ]);

        return true;
    }
}
