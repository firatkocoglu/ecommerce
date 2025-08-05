<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'notes',
        'added_at',
        'priority',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'priority' => 'integer',
    ];

    protected static function booted() {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy(
                'priority',
                'asc'
            )->orderBy(
                'added_at',
                'desc')
            ;});
    }

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
