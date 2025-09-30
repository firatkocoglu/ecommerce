<?php

namespace App\Services\ProductVariants;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductVariantService
{
    public function listByProductId(int $productId, bool $onlyActive = true, bool $cacheActive = true): Collection
    {
        // Check if the provided product ID is valid
        $int = filter_var($productId, FILTER_VALIDATE_INT, ['options' =>
            ['min_range' => 1]]);
        if ($int === false) {
            throw new \InvalidArgumentException('Invalid product ID provided.');
        }

        // Define key for naming cache
        $key = "products:{$productId}:variants:active:{$onlyActive}";

        // Base query
        $query = ProductVariant::query()
            ->where('product_id', $productId);

        // Filter only active variants if required
        $query = $onlyActive ? $query->where('status', 'active') : $query;

        // Eager load relationships
        $query = $query->with(['images']);

        return $cacheActive ?
            Cache::tags(['variants'])->remember($key, now()->addMinutes(2), function () use ($query) {
                return $query->get();
            })
            : $query->get();
    }

    public function findById(int $productId, int $variantId, bool $onlyActive = true, bool $cacheActive = true): ProductVariant
    {
        // Check if the provided IDs are valid
        $intProduct = filter_var($productId, FILTER_VALIDATE_INT, ['options' =>
            ['min_range' => 1]]);
        $intVariant = filter_var($variantId, FILTER_VALIDATE_INT, ['options' =>
            ['min_range' => 1]]);
        if ($intProduct === false || $intVariant === false) {
            throw new \InvalidArgumentException('Invalid product ID or variant ID provided.');
        }

        // Define key for naming cache
        $key = "products:{$productId}:variant:{$variantId}:active:{$onlyActive}";

        // Base query
        $query = ProductVariant::query()
            ->where('product_id', $productId)
            ->where('id', $variantId);

        // Filter only active variants if required
        $query = $onlyActive ? $query->where('status', 'active') : $query;

        // Eager load relationships
        $query = $query->with(['images']);

        return $cacheActive
            ? Cache::tags(['variants'])->remember($key, now()->addMinutes(2), function () use ($query) {
                return $query->firstOrFail();
        })
            : $query->firstOrFail();
    }

    /**
     * @throws Throwable
     */
    public function create(Product $product, array $data): ProductVariant
    {
        // Implement variant creation logic here
        return DB::transaction(function () use ($data, $product) {
            // Find product and lock it for variant update
            Product::query()->where('id', $product->id)->lockForUpdate()->firstOrFail();

            // Assign the product ID to the variant data
            $data['product_id'] = (int) $product->id;

            // Create the product variant
            $variant = ProductVariant::create($data);
            DB::afterCommit(function () {
                // Clear relevant caches if necessary
                Cache::tags(['products, variants'])->flush();
            });


            return $variant->load(['images']);
        });
    }

    /**
     * @throws Throwable
     */
    public function update(Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        // Ensure the variant actually belongs to the given product
        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        // Implement variant update logic here
        return DB::transaction(function () use ($data, $product, $variant) {
            // Lock the product row to prevent concurrent updates
            $locked = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('id', $variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Update the variant with new data but don't save to db
            $locked->fill($data);

            if (! $locked->isDirty()) {
                // No changes detected, return the existing variant
                return $locked;
            }

            $locked->save();

            DB::afterCommit(function () {
                // Clear relevant caches if necessary
                Cache::tags(['products, variants'])->flush();
            });

            return $variant;
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(Product $product, ProductVariant $variant): void
    {
        // Ensure the variant actually belongs to the given product
        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        DB::transaction(function () use ($product, $variant) {
            // Ensure the variant actually belongs to the given product and lock the row
            $locked = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('id', $variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Perform the delete on the locked instance
            $locked->delete();

            DB::afterCommit(function () {
                Cache::tags(['products, variants'])->flush();
            });
        });
    }
}
