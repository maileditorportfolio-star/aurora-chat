<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'admins';

    protected $fillable = ['email', 'password', 'name'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];
}