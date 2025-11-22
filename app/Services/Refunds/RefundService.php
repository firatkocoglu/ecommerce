<?php

namespace App\Services\Refunds;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Refund;
use App\Models\ReturnRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Stripe\Event;
use Throwable;

class RefundService
{
    /**
     * @throws Throwable
     */
    public function createRefund(array $data): void
    {
        // Logic to create a refund
        // Data array includeS order_id, amount, reason;
        $returnRequestId = $data['return_request_id'];
        $orderId = $data['order_id'];
        $userId = $data['user_id'];
        $amount = $data['amount'];
        $currency = $data['currency_code'];
        $reason = $data['reason'];

        if ($amount <= 0) {
            throw new Exception('Refund amount must be greater than zero.');
        }

        if (! $returnRequestId || ! $orderId || ! $userId) {
            throw new Exception('Return request ID, order ID, user ID are required.');
        }

        DB::transaction(function () use ($returnRequestId, $orderId, $userId, $amount, $currency, $reason) {
            // Check if the return request exists and is approved
            $returnRequest = ReturnRequest::whereKey($returnRequestId)
                ->where('order_id', $orderId)
                ->where('user_id', $userId)
                ->where('status', 'approved')
                ->with('order')
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $returnRequest->order->payment;

            if ($payment->status->value !== PaymentStatus::Completed->value) {
                throw new Exception('Cannot process refund for unpaid order.');
            }

            $idempotencyKey = 'refund:create:pi:'.$payment->transaction_id.':rr:'.$returnRequest->id;

            $stripeClient = new \Stripe\StripeClient(
                config('services.stripe.secret')
            );

            // Create refund record in the database
            Refund::create([
                'user_id' => $returnRequest->user_id,
                'return_request_id' => $returnRequest->id,
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'currency_code' => $currency,
                'reason' => $reason,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
            ]);

            // Call stripe refund API
            $stripeClient->refunds->create([
                'payment_intent' => $payment->transaction_id,
                'amount' => (int) ($amount * 100), // amount in cents
                'metadata' => [
                    'return_request_id' => $returnRequest->id,
                    'order_id' => $orderId,
                    'idempotency_key' => $idempotencyKey,
                ],
            ]);
        });
    }

    /**
     * @throws Exception
     */
    public function handleRefundCreated(Event $event): void
    {
        $refundData = $event->data->object;

        if (! $refundData) {
            throw new Exception('Refund data not found.');
        }

        $refundId = $refundData->id;
        $idempotencyKey = $refundData->metadata->idempotency_key;
        $orderId = $refundData->metadata->order_id;
        $returnRequestId = $refundData->metadata->return_request_id;

        $refund = Refund::where('return_request_id', $returnRequestId)
            ->where('status', 'pending')
            ->where('order_id', $orderId)
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->firstOrFail();

        // Update refund status based on Stripe refund status
        $refund->update([
            'status' => 'completed',
            'refunded_at' => now(),
            'provider_event_id' => $event->id,
            'provider_refund_id' => $refundId,
            'provider_payload' => json_encode($event),
        ]);
    }

    /**
     * @throws Exception
     */
    public function handleChargeRefunded(Event $event): void
    {
        $refundData = $event->data->object;

        if (! $refundData) {
            throw new Exception('Refund data not found in charge refunded event.');
        }

        $orderId = $refundData->metadata->order_id;
        $returnRequestId = $refundData->refunds->data[0]->metadata->return_request_id;

        $order = Order::whereKey($orderId)
            ->lockForUpdate()
            ->firstOrFail();

        $returnRequest = ReturnRequest::whereKey($returnRequestId)
            ->where('order_id', $orderId)
            ->where('status', 'approved')
            ->lockForUpdate()
            ->firstOrFail();

        // Update order and return request status as completed
        $order->update([
            'status' => 'returned',
            'updated_at' => now(),
        ]);

        $returnRequest->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);
    }
}
