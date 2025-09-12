<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'amount',
        'notes',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'amount' => 'decimal:2',
        'type' => TransactionTypes::class,
        'status' => TransactionStatus::class,
    ];

    protected $appends = [
        'formatted_amount',
    ];

    protected $with = [
        'user',
        'admin',
        'payment',
        'refund',
    ];

    protected $touches = [
        'order',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format((float) $this->amount, 2, ',', '.').' ₺';
    }
}
