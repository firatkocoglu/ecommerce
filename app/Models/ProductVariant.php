<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'color',
        'size',
        'price',
        'weight',
        'sku',
        'status',
        'product_id',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
    ];

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', ProductStatus::Draft->value);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', ProductStatus::Archived->value);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id')->whereNull('product_id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_variant_id')->whereNull('product_id')->where('is_primary', true)->orderBy('sort_order');
    }

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

    public function coverImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_variant_id')->whereNull('product_id')->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_variant_id');
    }
}
