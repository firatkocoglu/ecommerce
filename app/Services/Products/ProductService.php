<?php

namespace App\Services\Products;

use App\Enums\RefundStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Throwable;

class ProductService
{
    public function listPaginated(int $perPage, bool $onlyActive = true, bool $cacheActive = true): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $currentPage = Paginator::resolveCurrentPage() ?: 1;

        $key = "products:paginated:page:{$currentPage}:perPage:{$perPage}:active:{$onlyActive}";

        // Implementation for listing products with pagination
        $query = Product::query();

        // Filter only active products if required
        $query = $onlyActive ? $query->where('status', 'active') : $query;

        // Eager load relationships and counts
        $query = $query->with(['categories:id,name,slug',
            'images',
            'variants.images',
            'primaryImage',
            'coverImage'])
            ->withCount(['variants', 'images']);

        // Cache the result if caching is enabled
        return $cacheActive ? Cache::tags(['products'])->remember($key, now()->addMinutes(2), function () use ($query, $perPage) {
            return $query->paginate($perPage);
        }) : $query->paginate($perPage);
    }

    public function findById(int $id, $onlyActive = true, $cacheActive = true): Product
    {
        // Implementation for finding a product by its ID

        // Define key for naming cache
        $key = "products:show:id:{$id}:active:{$onlyActive}";

        // Base query
        $query = Product::query();

        // Filter only active products if required
        $query = $onlyActive ? $query->where('status', 'active') : $query;

        // Eager load relationships and counts
        $query = $query->with(['categories:id,name,slug',
            'images' => fn ($q) => $q->orderByDesc('is_primary'),
            'variants.images' => fn ($q) => $q->orderByDesc('is_primary'),
            'primaryImage',
            'coverImage'])
            ->withCount(['variants', 'images']);

        // Cache the result if caching is enabled
        return $cacheActive ? Cache::tags(['products'])->remember($key, now()->addMinutes(2), function () use ($query, $id) {
            return $query->findOrFail($id);
        }) : $query->findOrFail($id);
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
    public function update(int $id, array $data): Product
    {
        // Implementation for updating an existing product
        return DB::transaction(function () use ($id, $data) {
            $product = Product::query()->lockForUpdate()->findOrFail($id);

            // Don't write the changes to db immediately
            $product->fill($data);

            // Any changes made?
            if (! $product->isDirty()) {
                return $product;
            }

            $product->save();

            DB::afterCommit(function () {
                Cache::tags(['products'])->flush();
            });

            return $product->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $product = Product::query()->lockForUpdate()->findOrFail($id);

            $product->delete();

            DB::afterCommit(function () {
                Cache::tags(['products'])->flush();
            });
        });
    }
}
