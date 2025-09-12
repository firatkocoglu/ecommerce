<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
