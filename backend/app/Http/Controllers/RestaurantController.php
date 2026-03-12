<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth('sanctum')->user();
        $adminSelector = fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone');
        $staffSelector = fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone');

        if ($currentUser && $currentUser->hasRole('superadmin')) {
            return response()->json(
                Restaurant::with(['admins' => $adminSelector, 'staffs' => $staffSelector])->get()
            );
        }

        if ($currentUser && $currentUser->hasAnyRole(['admin', 'staff'])) {
            $restaurantIds = $this->managedRestaurantIds($currentUser);
            if (empty($restaurantIds)) {
                return response()->json([]);
            }

            return response()->json(
                Restaurant::with(['admins' => $adminSelector, 'staffs' => $staffSelector])
                    ->whereIn('id', $restaurantIds)
                    ->get()
            );
        }

        return response()->json(
            Restaurant::with(['admins' => $adminSelector, 'staffs' => $staffSelector])
                ->where('active', true)
                ->get()
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $data['created_by'] = $user->id;

        try {
            $restaurant = Restaurant::create($data);

            if ($user->hasRole('admin')) {
                $adminRoleId = Role::where('name', 'admin')->value('id');
                if ($adminRoleId) {
                    $restaurant->users()->syncWithoutDetaching([
                        $user->id => ['role_id' => $adminRoleId],
                    ]);
                }
            }

            return response()->json(
                $restaurant->load([
                    'admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone'),
                    'staffs' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')
                ]),
                201
            );
        } catch (\Exception $e) {
            // fallback and log
            saveFallbackData(['action' => 'create_restaurant', 'data' => $data]);
            Log::channel('db_errors')->error('Failed to create restaurant', ['exception' => $e]);

            return response()->json(['message' => 'Database error, operation saved for later'], 500);
        }
    }

    public function show(Restaurant $restaurant)
    {
        $user = request()->user();

        if ($user) {
            if ($user->hasRole('superadmin')) {
                return response()->json(
                    $restaurant->load([
                        'admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone'),
                        'staffs' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')
                    ])
                );
            }

            if ($user->hasAnyRole(['admin', 'staff']) && !$this->canAccessRestaurant($user, (int) $restaurant->id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($user->hasRole('cliente') && !$restaurant->active) {
                return response()->json(['message' => 'Restaurant not available'], 404);
            }
        } elseif (!$restaurant->active) {
            return response()->json(['message' => 'Restaurant not available'], 404);
        }

        return response()->json(
            $restaurant->load([
                'admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone'),
                'staffs' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')
            ])
        );
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $isLinkedAdmin = $restaurant->admins()->where('users.id', $user->id)->exists();
            if (!$isLinkedAdmin && $restaurant->created_by !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'active' => 'boolean',
        ]);

        try {
            $restaurant->update($data);
            return response()->json(
                $restaurant->load([
                    'admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone'),
                    'staffs' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')
                ])
            );
        } catch (\Exception $e) {
            saveFallbackData(['action' => 'update_restaurant', 'id' => $restaurant->id, 'data' => $data]);
            Log::channel('db_errors')->error('Failed to update restaurant', ['exception' => $e]);
            return response()->json(['message' => 'Database error, operation saved for later'], 500);
        }
    }

    public function destroy(Restaurant $restaurant)
    {
        $user = request()->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $isLinkedAdmin = $restaurant->admins()->where('users.id', $user->id)->exists();
            if (!$isLinkedAdmin && $restaurant->created_by !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $restaurant->delete();
        return response()->json(null, 204);
    }

    public function syncAdmins(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $isLinkedAdmin = $restaurant->admins()->where('users.id', $user->id)->exists();
            if (!$isLinkedAdmin && $restaurant->created_by !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $data = $request->validate([
            'admin_ids' => 'required|array|size:1',
            'admin_ids.*' => 'integer|exists:users,id',
        ]);

        $requestedAdminIds = collect($data['admin_ids'])
            ->unique()
            ->values()
            ->all();

        if (count($requestedAdminIds) !== 1) {
            return response()->json(['message' => 'Each restaurant must have exactly one admin'], 422);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $allowedIds = [$user->id];
            $hasForbiddenIds = count(array_diff($requestedAdminIds, $allowedIds)) > 0;
            if ($hasForbiddenIds) {
                return response()->json(['message' => 'Admin can only assign themselves'], 403);
            }
        }

        $validAdminIds = User::whereIn('id', $requestedAdminIds)
            ->whereHas('role', function ($query) {
                $query->where('name', 'admin');
            })
            ->pluck('id')
            ->all();

        if (count($validAdminIds) !== count($requestedAdminIds)) {
            return response()->json(['message' => 'Some users are not admins'], 422);
        }

        $adminRoleId = Role::where('name', 'admin')->value('id');
        if (!$adminRoleId) {
            return response()->json(['message' => 'Admin role not found'], 500);
        }

        $currentAdminIds = $restaurant->admins()->pluck('users.id')->all();
        $adminIdsToDetach = array_diff($currentAdminIds, $validAdminIds);
        if (!empty($adminIdsToDetach)) {
            $restaurant->users()->detach($adminIdsToDetach);
        }

        foreach ($validAdminIds as $adminId) {
            $restaurant->users()->syncWithoutDetaching([
                $adminId => ['role_id' => $adminRoleId],
            ]);
        }

        return response()->json([
            'message' => 'Restaurant admins updated',
            'restaurant' => $restaurant->load([
                'admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone'),
                'staffs' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')
            ]),
        ]);
    }

    public function syncStaffs(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $isLinkedAdmin = $restaurant->admins()->where('users.id', $user->id)->exists();
            if (!$isLinkedAdmin && $restaurant->created_by !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $data = $request->validate([
            'staff_ids' => 'present|array',
            'staff_ids.*' => 'integer|exists:users,id',
        ]);

        $requestedStaffIds = collect($data['staff_ids'])
            ->unique()
            ->values()
            ->all();

        if (count($requestedStaffIds) === 0) {
            $currentStaffIds = $restaurant->staffs()->pluck('users.id')->all();
            if (!empty($currentStaffIds)) {
                $restaurant->users()->detach($currentStaffIds);
            }

            return response()->json([
                'message' => 'Restaurant staffs updated',
                'restaurant' => $restaurant->load([
                    'admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone'),
                    'staffs' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')
                ]),
            ]);
        }

        $restaurantAdminIds = $restaurant->admins()->pluck('users.id')->values()->all();
        $restaurantAdminId = count($restaurantAdminIds) === 1 ? (int) $restaurantAdminIds[0] : null;

        if ($restaurantAdminId === null) {
            return response()->json([
                'message' => 'Assign exactly one admin to this restaurant before assigning staff',
            ], 422);
        }

        $staffQuery = User::whereIn('id', $requestedStaffIds)
            ->whereHas('role', function ($query) {
                $query->where('name', 'staff');
            });

        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $staffQuery->where('created_by', $user->id);
        }

        if ($user->hasRole('superadmin')) {
            $staffQuery->where('created_by', $restaurantAdminId);
        }

        $validStaffIds = $staffQuery->pluck('id')->all();

        if (count($validStaffIds) !== count($requestedStaffIds)) {
            return response()->json(['message' => 'Some users are not valid staff for this assignment'], 422);
        }

        $staffRoleId = Role::where('name', 'staff')->value('id');
        if (!$staffRoleId) {
            return response()->json(['message' => 'Staff role not found'], 500);
        }

        // Restriction: one staff can only be assigned to one restaurant
        $conflictingStaffIds = DB::table('user_restaurant')
            ->whereIn('user_id', $validStaffIds)
            ->where('role_id', $staffRoleId)
            ->where('restaurant_id', '!=', $restaurant->id)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if (!empty($conflictingStaffIds)) {
            return response()->json([
                'message' => 'Each staff can only be assigned to one restaurant',
                'conflicting_staff_ids' => $conflictingStaffIds,
            ], 422);
        }

        $currentStaffIds = $restaurant->staffs()->pluck('users.id')->all();
        $staffIdsToDetach = array_diff($currentStaffIds, $validStaffIds);
        if (!empty($staffIdsToDetach)) {
            $restaurant->users()->detach($staffIdsToDetach);
        }

        foreach ($validStaffIds as $staffId) {
            $restaurant->users()->syncWithoutDetaching([
                $staffId => ['role_id' => $staffRoleId],
            ]);
        }

        return response()->json([
            'message' => 'Restaurant staffs updated',
            'restaurant' => $restaurant->load([
                'admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone'),
                'staffs' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')
            ]),
        ]);
    }
}
