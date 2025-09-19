<?php

namespace App\Services\Products;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Throwable;

class ProductService
{
    public function listPaginated(int $perPage, ?int $afterId = null, bool $cacheActive = true): CursorPaginator
    {
        $perPage = max(1, min($perPage, 100));

        $cursor = request()->query('cursor');
        $key = "products:cursor:".($cursor ?? null).":perPage:{$perPage}";

        // Implementation for listing products with pagination
        // Filter only active products
        // Order by ID ascending
        // Select only necessary fields
        // Include cover image URL as a subquery
        $query = Product::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->select(['id', 'name', 'price', 'slug', 'status'])
            ->selectSub(function ($sq) {
                $sq->from('product_images')
                    ->select('url')
                    ->whereColumn('product_images.product_id', 'products.id')
                    ->whereNull('product_variant_id')
                    ->orderByRaw('CASE WHEN is_primary THEN 0 ELSE 1 END')
                    ->orderBy('sort_order')
                    ->orderByDesc('id')
                    ->limit(1);
            }, 'cover_image_url');

        // Cache the result if caching is enabled
        return $cacheActive ? Cache::tags(['products'])->remember($key, now()->addMinutes(2), function () use ($query, $perPage, $cursor) {
            return $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
        }) : $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function findById(Product $product, $onlyActive = true, $cacheActive = true): Product
    {
        // Implementation for finding a product by its ID
        // Define key for naming cache
        $key = "products:show:id:{$product->id}:active:{$onlyActive}";

        // Base query
        $query = Product::query();

        // Filter only active products if required
        $query = $onlyActive ? $query->where('status', 'active') : $query;

        // Eager load relationships and counts
        $query = $query->with(['categories:id,name,slug',
            'images' => fn ($q) => $q->orderByDesc('is_primary'),
            'variants' => fn ($q) => $q->select(['id', 'product_id', 'sku', 'price']),
            'coverImage']);

        $fetch = fn () => $query->whereKey($product->id)->firstOrFail();

        // Cache the result if caching is enabled
        return $cacheActive
            ? Cache::tags(['products'])->remember($key, now()->addMinutes(2), $fetch)
            : $fetch();
    }

    /**
     * @throws Throwable
     */
    public function create(array $data): Product
    {
        // Extract category IDs from data
        $rawCategoryIds = Arr::pull($data, 'categories', []);

        // Clean and validate category IDs
        $categoryIds = collect($rawCategoryIds)->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->toArray();

        // Create the product
        return DB::transaction(function () use ($data, $categoryIds) {
            // Validate category IDs
            $validIds = Category::query()->whereIn('id', $categoryIds)->pluck('id')->toArray();
            // Create the product
            $product = Product::create($data);

            // Attach categories if any valid IDs are provided
            if(! empty($categoryIds)) {
                $product->categories()->sync($validIds);
            }

            DB::afterCommit(function () {
                Cache::tags(['products'])->flush();
            });

            return $product->load(['categories:id,name,slug']);
        });
    }

    /**
     * @throws Throwable
     */
    public function update(Product $product, array $data): Product
    {
        // Extract category IDs from data
        $rawCategoryIds = Arr::pull($data, 'categories', null);
        $categoryIds = $rawCategoryIds !== null  ? collect($rawCategoryIds)->map(fn($id) => (int)$id)
                ->filter(fn($id) => $id > 0)
                ->unique()
                ->values()
                ->toArray() : null;

        // Implementation for updating an existing product
        return DB::transaction(function () use ($product, $data, $categoryIds) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            // Sync categories only if category IDs provided
            if ($categoryIds !== null) {
                $validCategoryIds = Category::query()->whereIn('id', $categoryIds)->pluck('id')->toArray();
                $locked->categories()->sync($validCategoryIds);
            }

            // Don't write the changes to db immediately
            $locked->fill($data);

            // Any changes made?
            if (! $locked->isDirty()) {
                return $locked;
            }

            $locked->save();

            DB::afterCommit(function () {
                Cache::tags(['products'])->flush();
            });

            return $locked->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $locked->delete();

            DB::afterCommit(function () {
                Cache::tags(['products'])->flush();
            });
        });
    }
}
