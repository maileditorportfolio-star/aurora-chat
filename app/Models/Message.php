<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'conversation_id', 'sender_id', 'ciphertext',
        'sender_ephemeral_pubkey', 'iv', 'mac', 'message_id',
        'is_deleted', 'ttl_expires_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'ttl_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Message $message) {
            if (empty($message->ttl_expires_at)) {
                $message->ttl_expires_at = now()->addHours(72);
            }
            if (empty($message->message_id)) {
                $message->message_id = (string) Str::uuid();
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function toPublicArray(): array
    {
        return [
            'id' => (string) $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'ciphertext' => $this->ciphertext,
            'sender_ephemeral_pubkey' => $this->sender_ephemeral_pubkey,
            'iv' => $this->iv,
            'mac' => $this->mac,
            'is_deleted' => $this->is_deleted,
            'created_at' => $this->created_at?->toIso8601String(),
            'ttl_expires_at' => $this->ttl_expires_at?->toIso8601String(),
        ];
    }
}