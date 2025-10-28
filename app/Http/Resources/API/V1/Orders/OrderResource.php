<?php

namespace App\Http\Resources\API\V1\Orders;

use App\Http\Resources\API\V1\Address\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'grand_total' => $this->grand_total,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'currency_code' => $this->currency_code,
            'shipping_address' => AddressResource::make($this->whenLoaded('shipping_address')),
            'billing_address' => AddressResource::make($this->whenLoaded('billing_address')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            ];
    }
}
