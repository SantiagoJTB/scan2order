<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$user->hasAnyRole(['superadmin', 'admin', 'staff'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Table::query();
        if (!$user->hasRole('superadmin')) {
            $restaurantIds = $this->managedRestaurantIds($user);
            if (empty($restaurantIds)) {
                return response()->json([]);
            }
            $query->whereIn('restaurant_id', $restaurantIds);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'number' => 'required|integer',
            'capacity' => 'required|integer',
        ]);

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $data['restaurant_id'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $table = Table::create($data);
        return response()->json($table, 201);
    }

    public function show(Request $request, Table $table)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['superadmin', 'admin', 'staff'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->hasRole('superadmin') && !$this->canAccessRestaurant($user, (int) $table->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($table);
    }

    public function update(Request $request, Table $table)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $table->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'number' => 'sometimes|required|integer',
            'capacity' => 'sometimes|required|integer',
        ]);

        if (isset($data['restaurant_id']) && $user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $data['restaurant_id'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $table->update($data);
        return response()->json($table);
    }

    public function destroy(Request $request, Table $table)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $table->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $table->delete();
        return response()->json(null, 204);
    }
}
