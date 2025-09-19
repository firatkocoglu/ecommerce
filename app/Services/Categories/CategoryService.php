<?php

namespace App\Services\Categories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryService
{
    public function listPaginated(int $perPage = 20): LengthAwarePaginator
    {
        return Category::query()
            ->withCount(['children', 'products'])
            ->with(['parent:id,name,slug'])
            ->latest('id')
            ->paginate($perPage);
    }

    public function tree(int $depth = 2): Collection
    {

        // Set cache time and parameter
        $ttl = now()->addMinutes(45 + random_int(0, 10));
        $key = "categories:tree:depth:{$depth}";

        return Cache::tags(['categories'])->remember($key, $ttl, function () use ($depth) {
            $with = ['children:id,name,slug,parent_id'];

            // Limit depth of category tree
            $current = 'children';
            for ($i = 1; $i < $depth; $i++) {
                $current .= '.children';
                $with[] = $current.':id,name,slug,parent_id';
            }

            return Category::query()
                ->whereNull('parent_id')
                ->with($with)
                ->orderBy('name')
                ->get();
        });
    }

    public function findById(Category $category): Category
    {
        return Category::query()
            ->whereKey($category->id)
            ->with(['parent:id,name,slug', 'children:id,name,slug,parent_id'])
            ->withCount('products')
            ->firstOrFail();
    }

    public function findBySlug(string $slug): Category
    {
        return Category::query()
            ->with(['parent:id,name,slug', 'children:id,name,slug,parent_id'])
            ->withCount('products')
            ->where('slug', $slug)
            ->sole();
    }

    // Implement create, update and delete services

    /**
     * @throws Throwable
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $category = Category::create($data);

            DB::afterCommit(function () {
                // Flush existing cache
                Cache::tags(['categories'])->flush();
            });

            return $category->load(['parent:id,name,slug']);
        });
    }

    /**
     * @throws Throwable
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $locked = Category::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();

            // Don't write the changes to db immediately
            $locked->fill($data);

            // Any changes made?
            if (! $locked->isDirty()) {
                return $locked->load(['parent:id,name,slug', 'children:id,name,slug,parent_id']);
            }

            // If any changes made then start writing to db.
            $locked->save();

            DB::afterCommit(function () {
                Cache::tags(['categories'])->flush();
            });

            return $locked->refresh()->load(['parent:id,name,slug', 'children:id,name,slug,parent_id']);
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category) {
            // Lock category for deletion
            $locked = Category::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();

            if ($locked->children()->exists()) {
                abort(422, 'Cannot delete category with children.');
            }

            if ($locked->products()->exists()) {
                abort(422, 'Cannot delete category with products.');
            }

            $locked->delete();

            DB::afterCommit(function () {
                Cache::tags(['categories'])->flush();
            });
        });
    }
}
