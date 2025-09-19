<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'price',
        'weight',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->whereNull('product_variant_id')->orderBy('sort_order');
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

        if ($this->relationLoaded('primaryImage') && ! $this->primaryImage) {
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
}
