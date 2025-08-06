<?php

namespace App\Models;

use App\AuditTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'ip_address',
        'user_agent',
        'logged_at',
    ];

    protected $casts = [
        'action' => AuditTypes::class,
        'auditable_id' => 'integer',
        'before' => 'array',
        'after' => 'array',
        'logged_at' => 'datetime',
    ];

    protected $with = [
        'user',
        'admin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function getActionLabelAttribute()
    {
        return $this->action->label() ?? '-';
    }
}
