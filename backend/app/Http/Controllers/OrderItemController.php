<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function index()
    {
        return response()->json(OrderItem::all());
    }

    public function store(Request $request, Order $order = null)
    {
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

        // compute pricing if not supplied
        if (!isset($data['unit_price']) || !isset($data['subtotal'])) {
            $product = \App\Models\Product::findOrFail($data['product_id']);
            $data['unit_price'] = $product->price;
            $data['subtotal'] = $product->price * $data['quantity'];
        }

        $item = OrderItem::create($data);
        return response()->json($item, 201);
    }

    public function show(OrderItem $orderItem)
    {
        return response()->json($orderItem);
    }

    public function update(Request $request, OrderItem $orderItem)
    {
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

    public function destroy(OrderItem $orderItem)
    {
        $orderItem->delete();
        return response()->json(null, 204);
    }
}
