<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'grand_total',
        'payment_method',
        'status',
        'currency_code',
        'transaction_id',
        'gateway',
        'paid_at',
        'idempotency_key',
        'provider_event_id',
        'provider_payload',
        'fee_amount',
        'net_amount',
        'exchange_rate',
    ];

    protected $appends = [
        'formatted_amount',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'provider_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2).' '.strtoupper($this->currency);
    }
}
