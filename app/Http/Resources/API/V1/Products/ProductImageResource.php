<?php

namespace App\Http\Resources\API\V1\Products;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
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
            'url' => $this->url,
            'is_primary' => (bool) $this->is_primary ?? false,
            'sort_order' => (int) $this->sort_order ?? 0,
        ];
    }
}
