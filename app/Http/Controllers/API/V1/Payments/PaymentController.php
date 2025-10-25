<?php

namespace App\Http\Controllers\API\V1\Payments;

use App\Http\Requests\API\V1\Payments\CreatePaymentIntentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;

readonly class PaymentController
{
    public function __construct(private PaymentService $service) {}

    public function createPaymentIntent (CreatePaymentIntentRequest $request): JsonResponse
    {
        try {
            [$clientSecret, $paymentId] = $this->service->createPaymentIntent(
                $request->input('order_id'),
                $request->user()->id
            );

            return response()->json([
                'client_secret' => $clientSecret,
                'payment_id' => $paymentId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unable to create payment intent.',
                'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], 422);
        }
    }
}
