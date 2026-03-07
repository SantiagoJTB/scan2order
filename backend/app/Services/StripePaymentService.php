<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentService
{
    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent(Order $order, float $amount, string $currency = 'eur'): PaymentIntent
    {
        $secretKey = config('services.stripe.secret');

        if (!$secretKey) {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        Stripe::setApiKey($secretKey);

        return PaymentIntent::create([
            'amount' => (int) round($amount * 100),
            'currency' => Str::lower($currency),
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        $secret = config('services.stripe.webhook_secret');

        if (!$secret) {
            throw new \RuntimeException('Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent($payload, $signature, $secret);
    }
}
