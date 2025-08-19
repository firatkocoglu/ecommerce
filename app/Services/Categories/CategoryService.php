<?php

namespace App\Services\Categories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryService
{

    public function listPaginated(int $perPage = 20): LengthAwarePaginator{
        return Category::query()
        ->withCount('children')
        ->with(['parent:id,name,slug'])
        ->latest('id')
        ->paginate($perPage);
    }

    public function tree(int $depth = 2): Collection {
        $ttl = now()->addMinutes(45 + random_int(0, 10));
        $key = "categories:tree:depth:{$depth}";

        return Cache::tags(['categories'])->remember($key, $ttl, function () use ($depth) {
            $with = ['children:id,name,slug,parent_id'];

            // Limit depth of category tree
            $current = 'children';
            for ($i = 1; $i < $depth; $i++) {
                $current .= '.children';
                $with[] = $current . ':id,name,slug,parent_id';
            }

            return Category::query()
                ->whereNull('parent_id')
                ->with($with)
                ->orderBy('name')
                ->get();
            });
    }

    public function findById(int $id): Category {
        return Category::query()
            ->with(['parent:id,name,slug', 'children:id,name,slug,parent_id'])
            ->findOrFail($id);
    }

    public function findBySlug(string $slug): Category {
        return Category::query()
            ->with(['parent:id,name,slug', 'children:id,name,slug,parent_id'])
            ->where('slug', $slug)
            ->sole();
    }

    // Implement create, update and delete services
    public function create(array $data): Category {
        return DB::transaction(function () use ($data) {
            $category = Category::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'parent_id' => $data['parent_id'] ?? null,
            ]);
        })
    }
}