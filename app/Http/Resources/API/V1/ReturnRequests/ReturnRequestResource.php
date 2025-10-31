<?php

namespace App\Http\Resources\API\V1\ReturnRequests;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'reason' => $this->reason,
            'requested_at' => $this->created_at,
            'rma_number' => $this->rma_number ?? null,
            'items' => ReturnRequestItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
