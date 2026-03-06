<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    public function index()
    {
        return response()->json(Restaurant::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'active' => 'boolean',
        ]);

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
        $restaurant->delete();
        return response()->json(null, 204);
    }
}
