<?php

namespace App\Services\Products;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
use Throwable;

class ProductService
{
    public function listPaginated(int $perPage = 20, bool  $onlyActive = true, bool $cacheActive = true): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $currentPage = Paginator::resolveCurrentPage() ?: 1;

        $key = "products:paginated:page:{$currentPage}:perPage:{$perPage}:active:{$onlyActive}";

        // Implementation for listing products with pagination
        $query = Product::query();

        // Filter only active products if required
        $query = $onlyActive ?  $query->where('status', 'active') : $query;

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
        $key = "products:show:id:{$id}:active:{$onlyActive}";

        // Base query
        $query = Product::query();

        // Filter only active products if required
        $query = $onlyActive ?  $query->where('status', 'active') : $query;

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
        // Implementation for creating a new product
        return DB::transaction(function () use ($data) {
            // Create the product
            $product = Product::create($data);

            DB::afterCommit(function () {
                // Flush existing cache
                Cache::tags(['products'])->flush();
            });
            return $product;
        });
    }

    // Product related business logic will go here
}
