<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'reason',
        'status',
        'requested_at',
        'refunded_at',
        'rejected_at',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function getStatusLabelAttribute()
    {
        return $this->status->label();
    }

    public function getFormattedRefundAmountAttribute()
    {
        return number_format((float)$this->amount, 2, ',', '.') . ' ₺';
    }
}
