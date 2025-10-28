<?php

namespace App\Http\Resources\API\V1\Orders;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_gross_price' => $this->unit_gross_price,
            'subtotal_line_gross' => $this->subtotal_line_gross,
        ];
    }
}
