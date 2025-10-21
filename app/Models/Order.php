<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'grand_total',
        'source_cart_id',
        'shipping_address_id',
        'billing_address_id',
        'currency_code',
        'status',
        'payment_method',
        'cancelled_at',
    ];

    protected $casts = [
        'grand_total' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $appends = [
        'grand_total_lira',
        'shipping_cost_lira',
        'formatted_grand_total',
        'formatted_shipping_cost',
        'formatted_total_weight',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): Order|HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function payment(): HasOne|Order
    {
        return $this->hasOne(Payment::class);
    }

    public function getGrandTotalLiraAttribute(): float|int
    {
        return $this->grand_total / 100;
    }

    public function getShippingCostLiraAttribute(): float|int
    {
        return $this->shipping_cost / 100;
    }

    public function getFormattedGrandTotalAttribute(): string
    {
        return number_format($this->grand_total / 100, 2, ',', '.').' ₺';
    }

    public function getFormattedShippingCostAttribute(): string
    {
        return number_format($this->shipping_cost / 100, 2, ',', '.').' ₺';
    }

    public function getFormattedTotalWeightAttribute(): string
    {
        return number_format((float) $this->total_weight, 2, ',', '.').' kg';
    }
}
