<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Authenticatable implements MustVerifyEmail
{
    use HasRoles, HasFactory;

    protected $guard_name = 'admin';

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
        'password' => 'hashed',
    ];

    public function isSuperAdmin()
    {
        return $this->is_super_admin;
    }
}
