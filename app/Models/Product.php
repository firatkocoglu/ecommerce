<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'price',
        'weight',
        'track_stock',
        'allow_backorder',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
    ];

    #[Scope]
    protected function active($query)
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    #[Scope]
    protected function draft($query)
    {
        return $query->where('status', ProductStatus::Draft->value);
    }

    #[Scope]
    protected function archived($query)
    {
        return $query->where('status', ProductStatus::Archived->value);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->whereNull('product_variant_id');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')->whereNull('product_variant_id')->where('is_primary', true);
    }

    // This method returns the primary image if set, otherwise the first image in the list
    public function resolvedPrimaryImage()
    {
        if ($this->relationLoaded('primaryImage') && $this->primaryImage) {
            return $this->primaryImage;
        }

        if ($this->relationLoaded('primaryImage') && !$this->primaryImage) {
            if ($this->relationLoaded('images')) {
                return $this->images->sortBy('sort_order')->first();
            }

            return $this->images()->orderBy('sort_order')->first();
        }

        if ($primary = $this->primaryImage()->first()) {
            return $primary;
        }

        if ($this->relationLoaded('images')) {
            return $this->images->sortBy('sort_order')->first();
        }

        return $this->images()->orderBy('sort_order')->first();
    }

    public function coverImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')
            ->ofMany(
                ['sort_order' => 'min'],
                function ($query) {
                    $query->whereNull('product_variant_id');
                });
    }

    public function galleryFor(?ProductVariant $variant = null)
    {
        // If a variant is provided, prefer its gallery
        if ($variant) {
            // Use eager-loaded relation if available; otherwise fetch ordered collection
            $variantImages = $variant->relationLoaded('images')
                ? $variant->images
                : $variant->images()->orderBy('sort_order')->get();

            if ($variantImages->isNotEmpty()) {
                return $variantImages;
            }
        }

        // Fallback to product images
        if ($this->relationLoaded('images')) {
            return $this->images->sortBy('sort_order');
        }

        return $this->images()->orderBy('sort_order')->get();

    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag', 'product_id', 'tag_id');
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_id')->whereNull('product_variant_id');
    }

    public function toSearchableArray(): array
    {
        // Ensure relationships are loaded (only if not already loaded)
        $this->loadMissing([
            'categories:id,name',
            'variants:id,color,size,sku,price,product_id'
        ]);

        $categoryNames = $this->categories->pluck('name')->toArray();
        $colors = $this->variants->pluck('color')->toArray();
        $sizes = $this->variants->pluck('size')->toArray();
        $prices = $this->variants->pluck('price')->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'price' => $this->price / 100,
            'category' => $categoryNames,
            'variant_colors' => array_values(array_unique($colors)),
            'variant_sizes' => array_values(array_unique($sizes)),
            'min_price' => $prices ? min($prices) : (float)$this->price,
            'max_price' => $prices ? max($prices) : (float)$this->price,
        ];
    }
}
