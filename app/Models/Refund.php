<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'return_request_id',
        'order_id',
        'payment_id',
        'amount',
        'currency_code',
        'reason',
        'status',
        'requested_at',
        'refunded_at',
        'rejected_at',
        'idempotency_key',
        'provider_event_id',
        'provider_payload',
        'provider_refund_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'refunded_at' => 'datetime',
        'rejected_at' => 'datetime',
        'status' => RefundStatus::class,
    ];

    protected $appends = [
        'status_label',
        'formatted_refund_amount',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function getStatusLabelAttribute()
    {
        return $this->status->label();
    }

    public function getFormattedRefundAmountAttribute()
    {
        return number_format((float) $this->amount, 2, ',', '.').' ₺';
    }
}
