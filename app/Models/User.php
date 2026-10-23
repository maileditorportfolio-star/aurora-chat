<?php

declare(strict_types=1);

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'users';

    protected $fillable = [
        'short_id', 'email', 'password', 'google_id', 'name',
        'avatar_url', 'public_key', 'is_blocked', 'blocked_at',
        'report_count', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token', 'public_key'];

    protected $casts = [
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
        'report_count' => 'integer',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->short_id)) {
                $user->short_id = app(\App\Services\ShortIdService::class)->generate();
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ConversationMember::class, 'user_id');
    }

    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsReceived(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    public function getIsBlockedAttribute(?bool $value): bool
    {
        return $value || !is_null($this->blocked_at);
    }

    public function getPublicIdAttribute(): string
    {
        return $this->short_id;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function toPublicArray(): array
    {
        return [
            'id' => (string) $this->id,
            'public_id' => $this->short_id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'is_blocked' => $this->is_blocked,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}