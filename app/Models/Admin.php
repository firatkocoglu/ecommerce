<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;


class Admin extends Authenticatable implements MustVerifyEmail
{
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function isSuperAdmin()
    {
        return $this->is_super_admin;
    }
}
