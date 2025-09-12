<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'color',
        'size',
        'price',
        'weight',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id')->whereNull('product_id')->orderBy('sort_order');
    }

    public function primaryImage()
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

    public function coverImage()
    {
        return $this->hasOne(ProductImage::class, 'product_variant_id')->whereNull('product_id')->orderByDesc('is_primary')->orderBy('sort_order');
    }
}
