<?php

namespace App\Models;

use App\CouponType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'expires_at',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'type' => CouponType::class,
        'usage_limit' => 'integer',
        'used_count' => 'integer',
    ];

    protected $appends = [
        'formatted_value',
    ];

    public function productVariant() {
        return $this->belongsTo(ProductVariant::class);
    }

    public function isExpired(): bool {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    public function isAvailable(): bool {
        return $this->is_active && !$this->isExpired() && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function getFormattedValueAttribute(): string {
        return $this->type === CouponType::Percentage ? number_format($this->value, 2) . '%' : number_format($this->value, 2) . ' ₺';
    }
}
