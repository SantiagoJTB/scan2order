<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStripePaymentIntentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class PaymentController extends Controller
{
    public function __construct(private readonly StripePaymentService $stripePaymentService)
    {
    }

    public function createStripePaymentIntent(CreateStripePaymentIntentRequest $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::findOrFail($orderId);
        if ($authError = $this->authorizeOrderAccess($user, $order, true)) {
            return $authError;
        }

        $amount = (float) ($request->validated('amount') ?? $order->total);
        $currency = strtolower($request->validated('currency') ?? 'eur');

        try {
            $paymentIntent = $this->stripePaymentService->createPaymentIntent($order, $amount, $currency);

            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => 'stripe',
                'provider' => 'stripe',
                'provider_payment_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Stripe payment intent created',
                'payment' => $payment,
                'client_secret' => $paymentIntent->client_secret,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Stripe payment intent creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create Stripe payment intent',
            ], 500);
        }
    }

    public function createCashPayment(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::findOrFail($orderId);
        if ($authError = $this->authorizeOrderAccess($user, $order, true)) {
            return $authError;
        }

        $amount = (float) ($request->input('amount') ?? $order->total);
        $currency = strtolower($request->input('currency') ?? 'eur');
        $immediate = filter_var($request->input('immediate', false), FILTER_VALIDATE_BOOLEAN);

        try {
            if ($immediate) {
                $payment = Payment::where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->latest('id')
                    ->first();

                if ($payment) {
                    $payment->update([
                        'method' => 'cash',
                        'provider' => 'none',
                        'amount' => $amount,
                        'currency' => $currency,
                        'status' => 'succeeded',
                        'paid_at' => now(),
                    ]);
                } else {
                    $payment = Payment::create([
                        'order_id' => $order->id,
                        'method' => 'cash',
                        'provider' => 'none',
                        'provider_payment_id' => null,
                        'amount' => $amount,
                        'currency' => $currency,
                        'status' => 'succeeded',
                        'paid_at' => now(),
                    ]);
                }

                $order->update(['status' => 'paid']);
            } else {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'method' => 'cash',
                    'provider' => 'none',
                    'provider_payment_id' => null,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'message' => 'Cash payment created successfully',
                'payment' => $payment,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Cash payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create cash payment',
            ], 500);
        }
    }

    public function createTestPayment(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::findOrFail($orderId);
        if ($authError = $this->authorizeOrderAccess($user, $order, true)) {
            return $authError;
        }

        $amount = (float) ($request->input('amount') ?? $order->total);
        $currency = strtolower($request->input('currency') ?? 'eur');
        $method = $request->input('method', 'test');

        try {
            // For table and cash payments, create as pending (payment collected later)
            // For card payments, create as succeeded immediately
            $isPending = in_array($method, ['table', 'cash']);
            
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $method,
                'provider' => 'test',
                'provider_payment_id' => 'test_' . uniqid(),
                'amount' => $amount,
                'currency' => $currency,
                'status' => $isPending ? 'pending' : 'succeeded',
                'paid_at' => $isPending ? null : now(),
            ]);

            // Update order status to paid only if payment succeeded immediately
            if (!$isPending) {
                $order->update(['status' => 'paid']);
            }

            return response()->json([
                'message' => 'Test payment created successfully',
                'payment' => $payment,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Test payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create test payment',
            ], 500);
        }
    }

    public function show(Payment $payment): JsonResponse
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payment->load('order');
        if ($authError = $this->authorizeOrderAccess($user, $payment->order, false)) {
            return $authError;
        }

        return response()->json($payment->load('order'));
    }

    private function authorizeOrderAccess($user, ?Order $order, bool $allowClientWhenOwner): ?JsonResponse
    {
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($user->hasRole('superadmin')) {
            return null;
        }

        if ($user->hasRole('cliente')) {
            if (!$allowClientWhenOwner || (int) $order->user_id !== (int) $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return null;
        }

        if ($user->hasAnyRole(['admin', 'staff'])) {
            if (!$this->canAccessRestaurant($user, (int) $order->restaurant_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return null;
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        if ($signature === '') {
            return response()->json(['message' => 'Missing Stripe signature'], 400);
        }

        try {
            $event = $this->stripePaymentService->constructWebhookEvent($payload, $signature);

            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntentId = $event->data->object->id;

                DB::transaction(function () use ($paymentIntentId) {
                    $payment = Payment::where('provider_payment_id', $paymentIntentId)->first();

                    if (!$payment) {
                        Log::warning('Stripe webhook payment not found', [
                            'provider_payment_id' => $paymentIntentId,
                        ]);
                        return;
                    }

                    $payment->update([
                        'status' => 'succeeded',
                        'paid_at' => now(),
                    ]);

                    $payment->order()->update([
                        'status' => 'paid',
                    ]);
                });
            }

            if ($event->type === 'payment_intent.payment_failed') {
                $paymentIntentId = $event->data->object->id;
                Payment::where('provider_payment_id', $paymentIntentId)->update([
                    'status' => 'failed',
                ]);
            }

            return response()->json(['received' => true]);
        } catch (SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid payload'], 400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }
}
