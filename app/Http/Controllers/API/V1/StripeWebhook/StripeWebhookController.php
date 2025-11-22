<?php

namespace App\Http\Controllers\API\V1\StripeWebhook;

use App\Services\Payments\PaymentService;
use App\Services\Refunds\RefundService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

readonly class StripeWebhookController
{
    public function __construct(private PaymentService $paymentService, private RefundService $refundService) {}

    /**
     * @throws Throwable
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $signature, $secret
            );

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }
        $eventType = $event->type;

        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->paymentService->handlePaymentIntentSucceeded($event);
                    break;
                case 'payment_intent.payment_failed':
                    $this->paymentService->handlePaymentIntentFailed($event);
                    break;
                case 'refund.created':
                    $this->refundService->handleRefundCreated($event);
                    break;
                case 'charge.refunded':
                    $this->refundService->handleChargeRefunded($event);
                    break;
                default:
                    return response()->json(['ignored' => $event->type], 200);

            }
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Webhook handling failed',
                'type' => $eventType,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json(['status' => 'success'], 200);
    }
}
