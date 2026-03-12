<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Category::query();

        if ($user) {
            if ($user->hasRole('cliente')) {
                return response()->json([]);
            }

            if ($user->hasAnyRole(['admin', 'staff'])) {
                $restaurantIds = $this->managedRestaurantIds($user);
                if (empty($restaurantIds)) {
                    return response()->json([]);
                }
                $query->whereIn('restaurant_id', $restaurantIds);
            } elseif (!$user->hasRole('superadmin')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
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
            'name' => 'required|string',
        ]);

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $data['restaurant_id'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function show(Request $request, Category $category)
    {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['admin', 'staff']) && !$this->canAccessRestaurant($user, (int) $category->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user && $user->hasRole('cliente')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $category->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'name' => 'sometimes|required|string',
        ]);

        if (isset($data['restaurant_id']) && $user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $data['restaurant_id'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->update($data);
        return response()->json($category);
    }

    public function destroy(Request $request, Category $category)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $category->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->delete();
        return response()->json(null, 204);
    }
}
