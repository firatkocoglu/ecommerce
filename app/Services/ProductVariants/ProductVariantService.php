<?php

namespace App\Services\ProductVariants;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

class ProductVariantService
{
    public function listByProductId(int $productId, bool $onlyActive = true): Collection
    {

        // Base query
        $query = ProductVariant::query()
            ->where('product_id', $productId);

        // Filter only active variants if required
        $query = $onlyActive ? $query->where('status', 'active') : $query;

        // Eager load relationships
        $query = $query->with(['images']);

        return $query->get();
    }

    public function findById(int $productId, int $variantId,  bool $onlyActive = true): ProductVariant
    {
        // Base query
        $query = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('id', $variantId);

        // Filter only active variants if required
        $query = $onlyActive ? $query->where('status', 'active') : $query;

        // Eager load relationships
        $query = $query->with(['images']);

        return $query->firstOrFail();
    }
}
