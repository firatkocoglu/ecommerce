<?php

namespace App\Http\Resources\API\V1\Products;

use App\Http\Resources\API\V1\Categories\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attrs = $this->resource->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->when(array_key_exists('description', $attrs), fn () => $this->description),
            'price' => $this->price,
            'weight' => $this->when(array_key_exists('weight', $attrs), fn () => $this->weight),
            'status' => $this->status,
            'categories' => $this->when(array_key_exists('categories', $attrs), fn () => CategoryResource::collection($this->whenLoaded('categories'))),
            'variants' => $this->when(array_key_exists('variants', $attrs), fn () => ProductVariantResource::collection($this->whenLoaded('variants'))),
            'cover_image_url' => $this->when(array_key_exists('cover_image_url', $attrs), fn () => $this->cover_image_url),
        ];
    }
}
