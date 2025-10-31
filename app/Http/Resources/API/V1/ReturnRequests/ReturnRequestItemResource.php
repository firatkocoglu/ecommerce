<?php

namespace App\Http\Resources\API\V1\ReturnRequests;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'return_request_id' => $this->return_request_id,
            'order_item_id' => $this->order_item_id,
            'quantity' => $this->quantity,
            'condition' => $this->condition,
        ];
    }
}
