<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        
        // Verificar estado
        if ($user->status !== 'active') {
            return response()->json(['message' => 'User account is not active'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $this->userWithRole($user),
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($this->userWithRole($request->user()));
    }

    /**
     * Format user with role and permissions.
     */
    private function userWithRole($user)
    {
        return [
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
        ];
    }
}
