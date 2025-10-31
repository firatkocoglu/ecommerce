<?php

namespace App\Http\Controllers\API\V1\Payments;

use App\Http\Requests\API\V1\Payments\CreatePaymentIntentRequest;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Throwable;

class PaymentController
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

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
}
