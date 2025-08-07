<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        "total_price", "shipping_cost", "total_weight", "shipping_address_id", "billing_address_id", "status", "payment_method", "payment_reference"
    ];

    protected $casts = [
        "total_price" => "integer", 
        "shipping_cost" => "integer",
        "total_weight" => "decimal:2",
    ];

    protected $attributes = [
        "status" => "pending"
    ];

    protected $appends = [        
        "total_price_lira",
        "shipping_cost_lira",
        "formatted_total_price",
        "formatted_shipping_cost",
        "formatted_total_weight",
    ];

    protected $with = [
        'items',
        'payment',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }


     public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress() {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress() {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function payment() {
        return $this->hasOne(Payment::class);
    }

    public function getTotalPriceLiraAttribute() {
        return $this->total_price / 100;
    }

    public function getShippingCostLiraAttribute() {
        return $this->shipping_cost / 100;
    }

    public function getFormattedTotalPriceAttribute() {
        return number_format($this->total_price / 100, 2, ',', '.') . ' ₺';
    }

    public function getFormattedShippingCostAttribute() {
        return number_format($this->shipping_cost / 100, 2, ',', '.') . ' ₺';
    }

    public function getFormattedTotalWeightAttribute() {
        return number_format((float)$this->total_weight, 2, ',', '.') . ' kg';
    }
}

