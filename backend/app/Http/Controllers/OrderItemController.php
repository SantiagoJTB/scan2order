<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = OrderItem::with(['order', 'product']);

        if ($user->hasRole('cliente')) {
            $query->whereHas('order', function ($orderQuery) use ($user) {
                $orderQuery->where('user_id', $user->id);
            });
        } elseif ($user->hasAnyRole(['admin', 'staff'])) {
            $restaurantIds = $this->managedRestaurantIds($user);
            if (empty($restaurantIds)) {
                return response()->json([]);
            }

            $query->whereHas('order', function ($orderQuery) use ($restaurantIds) {
                $orderQuery->whereIn('restaurant_id', $restaurantIds);
            });
        } elseif (!$user->hasRole('superadmin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($query->get());
    }

    public function store(Request $request, Order $order = null)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // basic validation: product and quantity always needed
        $rules = [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ];

        if (!$order) {
            $rules['order_id'] = 'required|exists:orders,id';
        }

        $data = $request->validate($rules);

        if ($order) {
            $data['order_id'] = $order->id;
        }

        $targetOrder = $order ?: Order::findOrFail($data['order_id']);

        if ($user->hasRole('cliente') && (int) $targetOrder->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasAnyRole(['admin', 'staff']) && !$this->canAccessRestaurant($user, (int) $targetOrder->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->hasAnyRole(['superadmin', 'admin', 'staff', 'cliente'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // compute pricing if not supplied
        if (!isset($data['unit_price']) || !isset($data['subtotal'])) {
            $product = Product::findOrFail($data['product_id']);

            if ((int) $product->restaurant_id !== (int) $targetOrder->restaurant_id) {
                return response()->json(['message' => 'Product does not belong to order restaurant'], 422);
            }

            $data['unit_price'] = $product->price;
            $data['subtotal'] = $product->price * $data['quantity'];
        }

        $item = OrderItem::create($data);
        return response()->json($item, 201);
    }

    public function show(Request $request, OrderItem $orderItem)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orderItem->load('order');

        if ($user->hasRole('cliente') && (int) $orderItem->order?->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasAnyRole(['admin', 'staff']) && !$this->canAccessRestaurant($user, (int) $orderItem->order?->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($orderItem);
    }

    public function update(Request $request, OrderItem $orderItem)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orderItem->load('order');

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $orderItem->order?->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'order_id' => 'sometimes|required|exists:orders,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'integer',
            'unit_price' => 'numeric',
            'subtotal' => 'numeric',
        ]);

        $orderItem->update($data);
        return response()->json($orderItem);
    }

    public function destroy(Request $request, OrderItem $orderItem)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orderItem->load('order');

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin') && !$this->canAccessRestaurant($user, (int) $orderItem->order?->restaurant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $orderItem->delete();
        return response()->json(null, 204);
    }
}
