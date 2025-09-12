<?php

namespace App\Http\Resources\API\V1\Products;

use App\Http\Resources\API\V1\Categories\CategoryResource;
use App\Http\Resources\API\V1\ReviewResource;
use App\Http\Resources\API\V1\TagResource;
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'weight' => $this->weight,
            'status' => $this->status,
            'categories' => CategoryResource::collection($this->whenLoaded('categories'))
//            'images' => ProductImageResource::collection($this->whenLoaded('images')),
//            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
//            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
//            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
