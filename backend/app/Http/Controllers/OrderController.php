<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Order::with([
            'restaurant:id,name',
            'user:id,name',
            'orderItems.product:id,name,price',
            'payments:id,order_id,method,status,amount,currency,paid_at,created_at',
        ]);

        if ($user->hasRole('cliente')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasAnyRole(['admin', 'staff'])) {
            $restaurantIds = $this->managedRestaurantIds($user);
            if (empty($restaurantIds)) {
                return response()->json([]);
            }
            $query->whereIn('restaurant_id', $restaurantIds);
        } elseif (!$user->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $orders = $query->latest()->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'user_id' => 'nullable|exists:users,id',
            'table_id' => 'nullable|exists:tables,id',
            'type' => 'required|in:local,delivery',
            'status' => 'required|string',
            'total' => 'numeric',
            'notes' => 'nullable|string',
        ]);

        if ($user->hasRole('cliente')) {
            $isActiveRestaurant = Restaurant::where('id', $data['restaurant_id'])
                ->where('active', true)
                ->exists();

            if (!$isActiveRestaurant) {
                return response()->json(['message' => 'Restaurant not available'], 403);
            }

            $data['user_id'] = $user->id;
        } elseif ($user->hasAnyRole(['admin', 'staff'])) {
            if (!$this->canAccessRestaurant($user, (int) $data['restaurant_id'])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif (!$user->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::create($data);
        return response()->json($order, 201);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->hasRole('cliente') && (int) $order->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasAnyRole(['admin', 'staff']) && !$this->canAccessRestaurant($user, (int) $order->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->hasAnyRole(['superadmin', 'admin', 'staff', 'cliente'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($order->load([
            'restaurant:id,name',
            'user:id,name',
            'orderItems.product:id,name,price',
            'payments:id,order_id,method,status,amount,currency,paid_at,created_at',
        ]));
    }

    public function update(Request $request, Order $order)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->hasRole('cliente')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasAnyRole(['admin', 'staff']) && !$this->canAccessRestaurant($user, (int) $order->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->hasAnyRole(['superadmin', 'admin', 'staff'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'restaurant_id' => 'sometimes|required|exists:restaurants,id',
            'user_id' => 'nullable|exists:users,id',
            'table_id' => 'nullable|exists:tables,id',
            'type' => 'sometimes|required|in:local,delivery',
            'status' => 'sometimes|required|string',
            'total' => 'numeric',
            'notes' => 'nullable|string',
        ]);

        if (isset($data['restaurant_id']) && $user->hasAnyRole(['admin', 'staff'])) {
            if (!$this->canAccessRestaurant($user, (int) $data['restaurant_id'])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $order->update($data);
        return response()->json($order);
    }

    public function destroy(Request $request, Order $order)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $order->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->delete();
        return response()->json(null, 204);
    }
}
