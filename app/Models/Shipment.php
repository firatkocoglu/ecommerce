<?php

namespace App\Models;

use App\Enums\ShipmentCarrier;
use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'carrier',
        'status',
        'shipped_at',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'status' => ShipmentStatus::class,
        'carrier' => ShipmentCarrier::class,
    ];

    protected $appends = [
        'status_label',
        'carrier_label',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusLabelAttribute()
    {
        return $this->status?->label();
    }

    public function getCarrierLabelAttribute()
    {
        return $this->carrier?->label();
    }
}
