<?php

namespace App\Http\Resources\API\V1\Carts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'user_id' => $this->user_id,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'subtotal_gross' => $this->subtotal_gross,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
