<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_amount',
        'applied_at',  
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'discount_amount' => 'decimal:2',
    ];

    protected $appends = ['formatted_discount'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }


    public function getFormattedDiscountAttribute()
    {
        return number_format((float) $this->discount_amount, 2, ',', '.') . ' ₺';
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }  
}
