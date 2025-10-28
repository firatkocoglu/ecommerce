<?php

namespace App\Http\Controllers\API\V1\Payments;

use App\Http\Requests\API\V1\Payments\CreatePaymentIntentRequest;
use App\Services\Payments\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

readonly class PaymentController
{
    public function __construct(private PaymentService $paymentService) {}

    public function createPaymentIntent (CreatePaymentIntentRequest $request): JsonResponse
    {
        try {
            [$clientSecret, $paymentId] = $this->paymentService->createPaymentIntent(
                $request->validated()['order_id'],
                $request->user()->id
            );

            return response()->json([
                'client_secret' => $clientSecret,
                'payment_id' => $paymentId,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Unable to create payment intent.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }

    /**
     * @throws Throwable
     */
    public function handleStripeWebhook(Request $request) : JsonResponse
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
                case 'payment_intent.created':

                default:
                    return response()->json(['ignored' => $event->type], 200);

            }
       }
       catch (Exception $e) {
              return response()->json([
                  'error' => 'Webhook handling failed',
                  'type' => $eventType,
                    'message' => $e->getMessage(),
              ], 500);
       }

       return response()->json(['status' => 'success'], 200);
    }
}
