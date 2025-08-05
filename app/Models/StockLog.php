<?php

namespace App\Models;

use App\StockChange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_change',
        'quantity',
        'description',
        'logged_at',
    ];

    protected $casts = [
        'stock_change' => StockChange::class,
        'quantity' => 'integer',
        'logged_at' => 'datetime',
    ];

    protected $appends = [
        'change_label',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function getChangeLabelAttribute()
    {
        return $this->stock_change->label();
    }
}
