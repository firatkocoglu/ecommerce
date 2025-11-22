<?php

namespace App\Http\Resources\API\V1\Payments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'grand_total' => $this->grand_total,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->transaction_id,
        ];
    }
}
