<?php

namespace App\Models;

use App\Enums\ReturnRequestStatus;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $fillable = [
        'reason',
        'status',
        'requested_at',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'status' => ReturnRequestStatus::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }
}
