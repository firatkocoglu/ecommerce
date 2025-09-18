<?php

namespace App\Services\ProductVariants;

use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public function create(int $productId, array $data): ProductVariant
    {
        // Check if productId is provided
        if (! $productId) {
            throw new \InvalidArgumentException('Product ID is required to create a variant.');
        }

        // Check if productId is a valid integer
        if (!is_numeric($productId) || (int)$productId <= 0)
        {
            throw new \InvalidArgumentException('Invalid Product ID provided.');
        }

        // Implement variant creation logic here
        return DB::transaction(function () use ($data, $productId) {
            // Find product and lock it for variant update
            Product::query()->lockForUpdate()->findOrFail($productId);

            // Assign the product ID to the variant data
            $data['product_id'] = (int) $productId;

            // Create the product variant
            $variant = ProductVariant::create($data);
            DB::afterCommit(function () {
                // Clear relevant caches if necessary
                Cache::tags(['products'])->flush();
            });

            return $variant->load(['images']);
        });
    }

    public function update(int $productId, int $variantId, array $data): ProductVariant
    {
        // Implement variant update logic here
        return DB::transaction(function () use ($data, $productId, $variantId) {
            // Find the variant and lock it for update
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($variantId);

            // Find the product and lock it for variant update
            Product::query()->lockForUpdate()->findOrFail($productId);

            // Update the variant with new data but don't save to db
            $variant->fill($data);

            if (! $variant->isDirty()) {
                // No changes detected, return the existing variant
                return $variant;
            }

            $variant->save();

            DB::afterCommit(function () {
                // Clear relevant caches if necessary
                Cache::tags(['products'])->flush();
            });

            return $variant;
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(int $productId, int $variantId): void
    {
        DB::transaction(function () use ($productId, $variantId) {
            // Find the variant and lock it for deletion
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($variantId);

            // Find the product and lock it for variant update
            Product::query()->lockForUpdate()->findOrFail($productId);

            // Delete the variant
            $variant->delete();

            DB::afterCommit(function () {
                // Clear relevant caches if necessary
                Cache::tags(['products'])->flush();
            });
        });
    }

}
