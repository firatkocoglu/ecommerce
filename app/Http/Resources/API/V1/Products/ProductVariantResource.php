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
        $attrs = $this->resource->getAttributes();

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'price' => $this->when(array_key_exists('price', $attrs), fn () => (float) $this->price),
            'sku' => $this->when(array_key_exists('sku', $attrs), fn () => $this->sku),
            'color' => $this->when(array_key_exists('color', $attrs), fn () => $this->color),
            'size' => $this->when(array_key_exists('size', $attrs), fn () => $this->size),
            'weight' => $this->when(array_key_exists('weight', $attrs), fn () => (float) $this->weight),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
