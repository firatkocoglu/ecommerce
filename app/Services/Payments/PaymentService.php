<?php

namespace App\Services\Payments;


use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Throwable;

class PaymentService
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function createPaymentIntent(int $orderId, int $userId)
    {
        // Logic to create a payment intent
        $order = Order::query()
            ->whereKey($orderId)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Check if order is already paid or cancelled
        if (in_array($order->status, ['paid', 'cancelled'])) {
            throw new Exception('Payment already processed');
        }

        // Check if there is an existing payment intent for the order
        $existingIntent = Payment::query()
            ->where('order_id', $orderId)
            ->where('gateway', 'Stripe')
            ->whereIn('status', ['pending', 'requires_payment_method', 'requires_confirmation'])
            ->latest('id')
            ->first();

        // If an existing intent is found, return its details
        if ($existingIntent && $existingIntent->transaction_id) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $paymentIntent = PaymentIntent::retrieve($existingIntent->transaction_id);
            return [
                'client_secret' => $pi->client_secret,
                'payment_intent_id' => $pi->id,
            ];
        }

        return DB::transaction(function () use ($order, $userId) {
            Stripe::setApiKey(config('services.stripe.secret'));

            $amountMinor = (int) round($order->grand_total * 100);  // Convert to minor units (e.g., kuruş)
            $currency = $order->currency_code ?? config('services.stripe.currency');

            $idempotencyKey = 'pi:create:order:' . $order->id;

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountMinor,
                'currency' => (string)$currency,
                'metadata' => [
                    'order_id' => (string)$order->id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'gateway' => 'Stripe',
                'transaction_id' => $paymentIntent->id,
                'amount' => $order->grand_total,
                'currency' => $currency,
                'status' => $paymentIntent->status,
            ]);

            return [$paymentIntent->client_secret, $payment->id];
        });
    }
}
