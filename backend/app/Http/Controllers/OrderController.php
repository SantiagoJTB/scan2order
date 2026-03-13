<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private function resolveServiceMode(array $data, ?Order $order = null): string
    {
        $incoming = isset($data['service_mode']) ? (string) $data['service_mode'] : null;
        if (in_array($incoming, ['local', 'takeaway', 'pickup'], true)) {
            return $incoming;
        }

        $baseType = (string) ($data['type'] ?? $order?->type ?? 'local');
        return $baseType === 'local' ? 'local' : 'takeaway';
    }

    private function restaurantSupportsMode(Restaurant $restaurant, string $serviceMode): bool
    {
        return match ($serviceMode) {
            'local' => (bool) $restaurant->service_local_enabled,
            'takeaway' => (bool) $restaurant->service_takeaway_enabled,
            'pickup' => (bool) $restaurant->service_pickup_enabled,
            default => false,
        };
    }

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
            'service_mode' => 'nullable|in:local,takeaway,pickup',
            'status' => 'required|string',
            'total' => 'numeric',
            'notes' => 'nullable|string',
            'delivery_address' => 'nullable|string|max:500',
        ]);

        $restaurant = Restaurant::findOrFail((int) $data['restaurant_id']);
        $serviceMode = $this->resolveServiceMode($data);

        if (!$this->restaurantSupportsMode($restaurant, $serviceMode)) {
            return response()->json(['message' => 'El restaurante no ofrece esta modalidad de pedido'], 422);
        }

        $data['type'] = $serviceMode === 'local' ? 'local' : 'delivery';

        if ($serviceMode === 'takeaway' && trim((string) ($data['delivery_address'] ?? '')) === '') {
            return response()->json(['message' => 'Delivery address is required for takeaway orders'], 422);
        }

        if ($serviceMode !== 'takeaway') {
            $data['delivery_address'] = null;
        }

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
            'service_mode' => 'sometimes|nullable|in:local,takeaway,pickup',
            'status' => 'sometimes|required|string',
            'total' => 'numeric',
            'notes' => 'nullable|string',
            'delivery_address' => 'sometimes|nullable|string|max:500',
        ]);

        $resultingMode = $this->resolveServiceMode($data, $order);
        $resultingType = $resultingMode === 'local' ? 'local' : 'delivery';
        $resultingAddress = array_key_exists('delivery_address', $data)
            ? (string) ($data['delivery_address'] ?? '')
            : (string) ($order->delivery_address ?? '');

        $restaurant = isset($data['restaurant_id'])
            ? Restaurant::findOrFail((int) $data['restaurant_id'])
            : $order->restaurant;

        if (!$restaurant || !$this->restaurantSupportsMode($restaurant, $resultingMode)) {
            return response()->json(['message' => 'El restaurante no ofrece esta modalidad de pedido'], 422);
        }

        $data['type'] = $resultingType;

        if ($resultingMode === 'takeaway' && trim($resultingAddress) === '') {
            return response()->json(['message' => 'Delivery address is required for takeaway orders'], 422);
        }

        if ($resultingType === 'local' && !array_key_exists('delivery_address', $data)) {
            $data['delivery_address'] = null;
        }

        if ($resultingMode !== 'takeaway' && !array_key_exists('delivery_address', $data)) {
            $data['delivery_address'] = null;
        }

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
