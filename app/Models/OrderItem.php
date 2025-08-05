<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;   

    protected $fillable =[ 
        "quantity", "price", "weight", "name",
    ];

    protected $casts = [
        "price" => "integer",
        "weight" => "decimal:2",
        "quantity" => "integer",
    ];

    protected $appends = [
        "price_lira",
        "formatted_price",
        "total_price_lira",
        "total_weight",
        "formatted_total_price",
        "formatted_total_weight",
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function productVariant() {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getPriceLiraAttribute() {
        return $this->price / 100;
    }

    public function getFormattedPriceAttribute() {
        return number_format($this->price_lira, 2, ",", ".") . " ₺";
    }

    public function getTotalPriceLiraAttribute() {
        return ($this->price * $this->quantity) / 100;
    }

    public function getFormattedTotalPriceAttribute() {
        return number_format($this->total_price_lira, 2, ",", ".")  . " ₺";
    }

    public function getTotalWeightAttribute() {
        return $this->weight * $this->quantity;
    }

    public function getFormattedTotalWeightAttribute() {
        return number_format($this->total_weight,  2, ",", ".") . " kg";
    }
}

