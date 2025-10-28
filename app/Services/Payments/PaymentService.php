<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderService;
use Exception;
use Illuminate\Support\Facades\DB;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Throwable;

readonly class PaymentService
{
    public function __construct(private OrderService $orderService)
    {
    }
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

        $idempotencyKey = 'pi:create:order:' . $order->id;

        // Check if there is an existing payment intent for the order
        $existingIntent = Payment::query()
            ->where('order_id', $order->id)
            ->where('gateway', 'Stripe')
            ->where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['pending', 'requires_payment_method', 'requires_confirmation'])
            ->latest('id')
            ->first();

        // If an existing intent is found, return its details
        if ($existingIntent && $existingIntent->transaction_id) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $paymentIntent = PaymentIntent::retrieve($existingIntent->transaction_id);
            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        }

        return DB::transaction(function () use ($order, $userId, $idempotencyKey) {
            Stripe::setApiKey(config('services.stripe.secret'));

            $amountMinor = (int) round($order->grand_total * 100);  // Convert to minor units (e.g., kuruş)
            $currency = $order->currency_code ?? config('services.stripe.currency');

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountMinor,
                'currency' => (string)$currency,
                'metadata' => [
                    'order_id' => (string)$order->id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'gateway' => 'Stripe',
                'transaction_id' => $paymentIntent->id,
                'grand_total' => $order->grand_total,
                'currency_code' => $currency,
                'status' => $paymentIntent->status,
                'idempotency_key' => $idempotencyKey,
            ]);

            return [$paymentIntent->client_secret, $payment->id];
        });
    }

    /**
     * @throws Exception
     */


    /**
     * @throws Throwable
     */
    public function handlePaymentIntentSucceeded(Event $paymentEvent): void
    {
        $paymentIntent = $paymentEvent->data->object;

        DB::transaction(function () use ($paymentIntent, $paymentEvent) {
            $payment = Payment::where('transaction_id', $paymentIntent->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'completed')
            {
                return;
            }

            if (strtolower($payment->currency_code) != strtolower($paymentIntent->currency)
                || (int) ($payment->grand_total * 100) != $paymentIntent->amount_received
                || $payment->order_id != $paymentIntent->metadata->order_id
            ) {
                throw new Exception('Payment details do not match');
            }

            $this->markAsPaid($payment, $paymentIntent);
        });
    }

    /**
     * @throws Throwable
     */
    public function handlePaymentIntentFailed(Event $paymentEvent): void
    {
        $paymentIntent = $paymentEvent->data->object;
        DB::transaction(function () use ($paymentEvent, $paymentIntent) {
            $payment = Payment::where('transaction_id', $paymentIntent->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Update payment status to failed
            $payment->status = 'failed';
            $payment->provider_event_id = $paymentEvent->id;
            $payment->provider_payload = $paymentEvent->toArray();
            $payment->save();
        });
    }

    /**
     * @throws Throwable
     */
    private function markAsPaid(Payment $payment, PaymentIntent $paymentIntent): void
    {
            // Update payment record
            $payment->status = 'completed';
            $payment->paid_at = now();
            $payment->provider_event_id = $paymentIntent->id;
            $payment->provider_payload = $paymentIntent->toArray();
            $payment->save();

            \Illuminate\Log\log('payment updated, next update order status');

            // Update order status
            $this->orderService->markAsCompleted($payment->order_id);
    }
}

/**
{
  "id": "evt_1PQh2xHWA22C7Y7EZSvPoJWL",
  "object": "event",
  "api_version": "2024-10-22",
  "created": 1730247023,
  "livemode": false,
  "type": "payment_intent.succeeded",
  "data": {
    "object": {
      "id": "pi_3Q5PmTHWA22C7Y7E1VbZsQjS",
      "object": "payment_intent",
      "amount": 259900,
      "amount_received": 259900,
      "currency": "try",
      "status": "succeeded",
      "metadata": {
            "order_id": "42"
      },
      "payment_method": "pm_1Q5Pn3HWA22C7Y7EAtHWBjLs",
      "customer": null,
      "created": 1730247009
    }
  },
  "request": {
    "id": "req_mBZZqFj8J9Z8U2",
    "idempotency_key": null
  },
  "pending_webhooks": 1
}
  **/
