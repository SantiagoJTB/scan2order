<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(Payment::all());
    }

    public function store(Request $request, Order $order = null)
    {
        $rules = [
            'method' => 'required|string',
            'amount' => 'required|numeric',
            'paid_at' => 'nullable|date',
        ];

        if (!$order) {
            $rules['order_id'] = 'required|exists:orders,id';
        }

        $data = $request->validate($rules);

        if ($order) {
            $data['order_id'] = $order->id;
        }

        $payment = Payment::create($data);
        return response()->json($payment, 201);
    }

    public function show(Payment $payment)
    {
        return response()->json($payment);
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'order_id' => 'sometimes|required|exists:orders,id',
            'method' => 'sometimes|required|string',
            'amount' => 'numeric',
            'paid_at' => 'nullable|date',
        ]);

        $payment->update($data);
        return response()->json($payment);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->json(null, 204);
    }
}
