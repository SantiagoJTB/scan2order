<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Create a new user with specific role.
     * Superadmin can create Admin, Admin, Caja, Cocina
     * Admin can create Caja, Cocina
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Verify authorization
        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:admin,caja,cocina',
        ]);

        // Get role by name
        $role = Role::where('name', $data['role'])->first();
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Only superadmin can create admin users
        // Regular admin can only create caja and cocina
        if ($data['role'] === 'admin' && !$user->hasRole('superadmin')) {
            return response()->json(['message' => 'Only superadmin can create admin users'], 403);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin') && !in_array($data['role'], ['caja', 'cocina'])) {
            return response()->json(['message' => 'Admin can only create caja and cocina users'], 403);
        }

        // When creating admin, also create caja and cocina
        $createdUsers = [];
        
        if ($data['role'] === 'admin') {
            // Create admin
            $adminUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role_id' => $role->id,
                'created_by' => $user->id,
                'status' => 'active',
            ]);
            $createdUsers[] = $this->formatUser($adminUser);

            // Auto-create caja and cocina users for this admin
            $cajaRole = Role::where('name', 'caja')->first();
            $cocinaRole = Role::where('name', 'cocina')->first();

            if ($cajaRole) {
                $cajaUser = User::create([
                    'name' => "{$data['name']} - Caja",
                    'email' => "caja-{$adminUser->id}@scan2order.local",
                    'password' => Hash::make('password123'),
                    'role_id' => $cajaRole->id,
                    'created_by' => $user->id,
                    'status' => 'active',
                ]);
                $createdUsers[] = $this->formatUser($cajaUser);
            }

            if ($cocinaRole) {
                $cocinaUser = User::create([
                    'name' => "{$data['name']} - Cocina",
                    'email' => "cocina-{$adminUser->id}@scan2order.local",
                    'password' => Hash::make('password123'),
                    'role_id' => $cocinaRole->id,
                    'created_by' => $user->id,
                    'status' => 'active',
                ]);
                $createdUsers[] = $this->formatUser($cocinaUser);
            }
        } else {
            // Create caja or cocina single user
            $newUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role_id' => $role->id,
                'created_by' => $user->id,
                'status' => 'active',
            ]);
            $createdUsers[] = $this->formatUser($newUser);
        }

        return response()->json([
            'message' => 'User(s) created successfully',
            'users' => $createdUsers
        ], 201);
    }

    /**
     * Update user status.
     */
    public function updateStatus(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Only superadmin and admin (for their users) can update
        if (!$currentUser->hasRole('superadmin') && ($currentUser->hasRole('admin') && $user->created_by !== $currentUser->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $user->update(['status' => $data['status']]);
        
        return response()->json([
            'message' => 'User status updated',
            'user' => $this->formatUser($user)
        ]);
    }

    /**
     * List users (paginated).
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Only superadmin and admin can list users
        if (!$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::with(['role', 'creator']);

        // Admin only sees their created users
        if ($currentUser->hasRole('admin') && !$currentUser->hasRole('superadmin')) {
            $query->where('created_by', $currentUser->id)
                  ->orWhere('id', $currentUser->id);
        }

        $users = $query->get();

        // Format users
        $formattedUsers = $users->map(function($user) {
            return $this->formatUser($user);
        });

        return response()->json($formattedUsers);
    }

    /**
     * Get user by ID.
     */
    public function show(Request $request, User $user)
    {
        $currentUser = $request->user();

        // Users can only see themselves, admins can see their users, superadmin sees all
        if ($user->id !== $currentUser->id && 
            !($currentUser->hasRole('admin') && $user->created_by === $currentUser->id) &&
            !$currentUser->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($this->formatUser($user));
    }

    /**
     * Format user response.
     */
    private function formatUser($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'created_by' => $user->created_by,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
            ] : null,
            'created_at' => $user->created_at,
        ];
    }
}
