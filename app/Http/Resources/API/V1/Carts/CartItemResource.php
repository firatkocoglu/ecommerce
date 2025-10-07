<?php

namespace App\Http\Resources\API\V1\Carts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'variant_key' => $this->variant_key,
            'quantity' => $this->quantity,
            'unit_price_gross' => $this->unit_price_gross,
            'line_total_gross' => $this->line_total_gross,
        ];
    }
}
