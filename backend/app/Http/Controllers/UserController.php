<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private function isLastActiveSuperadmin(User $user): bool
    {
        $user->loadMissing('role');

        if ($user->role?->name !== 'superadmin') {
            return false;
        }

        $superadminRoleId = Role::where('name', 'superadmin')->value('id');
        if (!$superadminRoleId) {
            return false;
        }

        $activeSuperadmins = User::where('role_id', $superadminRoleId)
            ->where('status', 'active')
            ->count();

        return $activeSuperadmins <= 1 && $user->status === 'active';
    }

    /**
     * Create a new user with specific role.
     * Superadmin can create Admin, Caja, Cocina, Cliente
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
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:admin,staff,cliente',
            'assign_to_admin' => 'nullable|boolean',
            'admin_id' => 'nullable|integer|exists:users,id',
            'staff_password' => 'nullable|string|min:6',
        ]);

        // Get role by name
        $role = Role::where('name', $data['role'])->first();
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Only superadmin can create admin users
        // Regular admin can only create staff
        if ($data['role'] === 'admin' && !$user->hasRole('superadmin')) {
            return response()->json(['message' => 'Only superadmin can create admin users'], 403);
        }

        // Only superadmin can create cliente users
        if ($data['role'] === 'cliente' && !$user->hasRole('superadmin')) {
            return response()->json(['message' => 'Only superadmin can create cliente users'], 403);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin') && $data['role'] !== 'staff') {
            return response()->json(['message' => 'Admin can only create staff users'], 403);
        }

        // Create user
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
            $this->auditAction(
                actor: $user,
                action: 'user.create',
                resourceType: 'user',
                resourceId: $adminUser->id,
                targetUser: $adminUser,
                metadata: ['role' => 'admin'],
                ipAddress: $request->ip(),
                userAgent: (string) $request->userAgent()
            );

            // Auto-create staff user for this admin
            $staffRole = Role::where('name', 'staff')->first();

            if ($staffRole) {
                $staffPassword = $data['staff_password'] ?? 'password123';
                $staffUser = User::create([
                    'name' => "{$data['name']} - Staff",
                    'email' => "staff-{$adminUser->id}@scan2order.local",
                    'password' => Hash::make($staffPassword),
                    'role_id' => $staffRole->id,
                    'created_by' => $adminUser->id,
                    'status' => 'active',
                ]);
                $createdUsers[] = $this->formatUser($staffUser);
                $this->auditAction(
                    actor: $user,
                    action: 'user.create_linked_staff',
                    resourceType: 'user',
                    resourceId: $staffUser->id,
                    targetUser: $staffUser,
                    metadata: ['role' => 'staff', 'linked_to_admin_id' => $adminUser->id],
                    ipAddress: $request->ip(),
                    userAgent: (string) $request->userAgent()
                );
            }
        } else {
            $createdBy = $user->id;

            if ($user->hasRole('superadmin') && $data['role'] === 'staff') {
                $assignToAdmin = (bool) ($data['assign_to_admin'] ?? false);

                if ($assignToAdmin) {
                    if (empty($data['admin_id'])) {
                        return response()->json(['message' => 'Debes seleccionar un admin'], 422);
                    }

                    $assignedAdmin = User::with('role')->find($data['admin_id']);
                    if (!$assignedAdmin || $assignedAdmin->role?->name !== 'admin') {
                        return response()->json(['message' => 'El usuario seleccionado no es admin'], 422);
                    }

                    $createdBy = $assignedAdmin->id;
                } else {
                    $createdBy = null;
                }
            }

            // Create staff or cliente user
            $newUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role_id' => $role->id,
                'created_by' => $createdBy,
                'status' => 'active',
            ]);
            $createdUsers[] = $this->formatUser($newUser);
            $this->auditAction(
                actor: $user,
                action: 'user.create',
                resourceType: 'user',
                resourceId: $newUser->id,
                targetUser: $newUser,
                metadata: ['role' => $data['role'], 'created_by' => $createdBy],
                ipAddress: $request->ip(),
                userAgent: (string) $request->userAgent()
            );
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

        if ($data['status'] !== 'active' && $this->isLastActiveSuperadmin($user)) {
            return response()->json([
                'message' => 'Cannot deactivate the last active superadmin',
            ], 422);
        }

        $previousStatus = $user->status;
        $user->update(['status' => $data['status']]);

        $this->auditAction(
            actor: $currentUser,
            action: 'user.status_update',
            resourceType: 'user',
            resourceId: $user->id,
            targetUser: $user,
            metadata: ['from' => $previousStatus, 'to' => $data['status']],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );
        
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
     * Update user basic information.
     */
    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (!$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You cannot edit your own account from this section'], 422);
        }

        if ($currentUser->hasRole('admin') && !$currentUser->hasRole('superadmin')) {
            if ($user->created_by !== $currentUser->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!in_array($user->role?->name, ['staff'])) {
                return response()->json(['message' => 'Admin can only edit staff users'], 403);
            }
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        $this->auditAction(
            actor: $currentUser,
            action: 'user.update',
            resourceType: 'user',
            resourceId: $user->id,
            targetUser: $user,
            metadata: [
                'before' => $before,
                'after' => [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                ],
            ],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $this->formatUser($user->fresh('role'))
        ]);
    }

    /**
     * Delete user.
     * - Clientes can delete their own account
     * - Admins can only delete staff users they created
     * - Only superadmin can delete cliente accounts
     * - Orders remain in restaurants (user_id set to null via onDelete cascade)
     */
    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();
        $userRole = $user->role?->name;

        // Allow clients to delete their own account
        if ($currentUser->id === $user->id && $userRole === 'cliente') {
            $this->auditAction(
                actor: $currentUser,
                action: 'user.self_delete',
                resourceType: 'user',
                resourceId: $user->id,
                targetUser: $user,
                metadata: ['role' => 'cliente'],
                ipAddress: $request->ip(),
                userAgent: (string) $request->userAgent()
            );
            $user->delete();
            return response()->json([
                'message' => 'Account deleted successfully'
            ]);
        }

        // Prevent other roles from deleting themselves
        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account'], 422);
        }

        // Only superadmin and admin can delete other users
        if (!$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent admin from deleting cliente accounts (only superadmin can)
        if ($currentUser->hasRole('admin') && !$currentUser->hasRole('superadmin') && $userRole === 'cliente') {
            return response()->json(['message' => 'Only superadmin can delete cliente accounts'], 403);
        }

        // Admin can only delete staff users they created
        if ($currentUser->hasRole('admin') && !$currentUser->hasRole('superadmin')) {
            if ($user->created_by !== $currentUser->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($userRole !== 'staff') {
                return response()->json(['message' => 'Admin can only delete staff users'], 403);
            }
        }

        if ($this->isLastActiveSuperadmin($user)) {
            return response()->json([
                'message' => 'Cannot delete the last active superadmin',
            ], 422);
        }

        $deletedLinkedAccounts = 0;

        DB::transaction(function () use ($user, &$deletedLinkedAccounts) {
            // If deleting an admin, also delete their created staff accounts
            if ($user->role?->name === 'admin') {
                $deletedLinkedAccounts = User::where('created_by', $user->id)->delete();
            }

            $user->delete();
        });

        $this->auditAction(
            actor: $currentUser,
            action: 'user.delete',
            resourceType: 'user',
            resourceId: $user->id,
            targetUser: $user,
            metadata: [
                'target_role' => $userRole,
                'deleted_linked_accounts' => $deletedLinkedAccounts,
            ],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        $message = 'User deleted successfully';
        if ($deletedLinkedAccounts > 0) {
            $message .= " and {$deletedLinkedAccounts} linked account(s)";
        }

        return response()->json([
            'message' => $message,
            'deleted_linked_accounts' => $deletedLinkedAccounts
        ]);
    }

    /**
     * Update user password.
     * - Superadmin can change any user password.
     * - Admin can only change passwords of their own staff users.
     */
    public function updatePassword(Request $request, User $user)
    {
        $currentUser = $request->user();
        $targetUser = $user->loadMissing('role');

        if (!$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($currentUser->hasRole('admin') && !$currentUser->hasRole('superadmin')) {
            if ($targetUser->created_by !== $currentUser->id || $targetUser->role?->name !== 'staff') {
                return response()->json(['message' => 'Admin can only change passwords of their own staff users'], 403);
            }
        }

        $data = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $targetUser->update([
            'password' => $data['password']
        ]);

        $this->auditAction(
            actor: $currentUser,
            action: 'user.password_update',
            resourceType: 'user',
            resourceId: $targetUser->id,
            targetUser: $targetUser,
            metadata: ['by_role' => $currentUser->role?->name],
            ipAddress: $request->ip(),
            userAgent: (string) $request->userAgent()
        );

        return response()->json([
            'message' => 'Password updated successfully',
            'user' => $this->formatUser($targetUser)
        ]);
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
