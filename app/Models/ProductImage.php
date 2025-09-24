<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'folder',
        'alt_text',
        'width',
        'height',
        'mime',
        'size_bytes',
        'is_primary',
        'sort_order',
        'product_id',
        'product_variant_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'size_bytes' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
