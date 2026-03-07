<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth('sanctum')->user();
        $adminSelector = fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone');

        if ($currentUser && $currentUser->hasRole('superadmin')) {
            return response()->json(
                Restaurant::with(['admins' => $adminSelector])->get()
            );
        }

        if ($currentUser && $currentUser->hasRole('admin')) {
            return response()->json(
                Restaurant::with(['admins' => $adminSelector])
                    ->where(function ($query) use ($currentUser) {
                        $query->whereHas('admins', function ($adminQuery) use ($currentUser) {
                            $adminQuery->where('users.id', $currentUser->id);
                        })->orWhere('created_by', $currentUser->id);
                    })
                    ->get()
            );
        }

        return response()->json(
            Restaurant::with(['admins' => $adminSelector])
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
                $restaurant->load(['admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')]),
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
        return response()->json(
            $restaurant->load(['admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')])
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
                $restaurant->load(['admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')])
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
            'admin_ids' => 'required|array',
            'admin_ids.*' => 'integer|exists:users,id',
        ]);

        $requestedAdminIds = collect($data['admin_ids'])
            ->unique()
            ->values()
            ->all();

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
            'restaurant' => $restaurant->load(['admins' => fn ($query) => $query->select('users.id', 'users.name', 'users.email', 'users.phone')]),
        ]);
    }
}
