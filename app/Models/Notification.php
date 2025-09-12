<?php

namespace App\Models;

use App\Enums\NotificationTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'header',
        'content',
        'url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'type' => NotificationTypes::class,
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'type_label',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function getTypeLabelAttribute()
    {
        return $this->type->label();
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}
