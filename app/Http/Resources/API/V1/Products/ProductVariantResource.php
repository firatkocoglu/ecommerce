<?php

namespace App\Http\Resources\API\V1\Products;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'weight' => (float) $this->weight,
            'options' => $this->options,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
