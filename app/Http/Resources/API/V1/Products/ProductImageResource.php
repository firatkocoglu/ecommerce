<?php

namespace App\Http\Resources\API\V1\Products;

use App\Helpers\ImageUrlHelper;
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
            'public_id' => $this->public_id,
            'alt_text' => $this->alt_text,
            'width' => (int) $this->width,
            'height' => (int) $this->height,
            'mime' => $this->mime,
            'size_bytes' => (int) $this->size_bytes,
            'is_primary' => (bool) $this->is_primary ?? false,
            'sort_order' => (int) $this->sort_order ?? 0,
            'urls' => $this->public_id ? ImageUrlHelper::createCloudinaryUrl($this->public_id) : null,
        ];
    }
}
