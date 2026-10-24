<?php

declare(strict_types=1);

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable implements JWTSubject
{
    protected $table = 'admins';

    protected $fillable = ['email', 'password', 'name'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}