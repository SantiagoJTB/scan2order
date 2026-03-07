<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth('sanctum')->user();

        if ($currentUser && $currentUser->hasRole('superadmin')) {
            return response()->json(Restaurant::all());
        }

        if ($currentUser && $currentUser->hasRole('admin')) {
            return response()->json(
                Restaurant::where('created_by', $currentUser->id)->get()
            );
        }

        return response()->json(
            Restaurant::where('active', true)->get()
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
            return response()->json($restaurant, 201);
        } catch (\Exception $e) {
            // fallback and log
            saveFallbackData(['action' => 'create_restaurant', 'data' => $data]);
            Log::channel('db_errors')->error('Failed to create restaurant', ['exception' => $e]);

            return response()->json(['message' => 'Database error, operation saved for later'], 500);
        }
    }

    public function show(Restaurant $restaurant)
    {
        return response()->json($restaurant);
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$user->hasRole('superadmin') && $restaurant->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'active' => 'boolean',
        ]);

        try {
            $restaurant->update($data);
            return response()->json($restaurant);
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

        if ($user->hasRole('admin') && !$user->hasRole('superadmin') && $restaurant->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $restaurant->delete();
        return response()->json(null, 204);
    }
}
