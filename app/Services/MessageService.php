<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageService
{
    public function store(array $data): Message
    {
        return DB::transaction(function () use ($data) {
            $conv = Conversation::lockForUpdate()->find($data['conversation_id']);
            if (!$conv) {
                throw new \RuntimeException('Conversation not found');
            }

            $existing = Message::where('message_id', $data['message_id'])->first();
            if ($existing) {
                return $existing;
            }

            $ttlSeconds = (int) config('app.message_ttl_seconds', 259200);

            return Message::create([
                'conversation_id' => $data['conversation_id'],
                'sender_id' => $data['sender_id'],
                'ciphertext' => $data['ciphertext'],
                'sender_ephemeral_pubkey' => $data['sender_ephemeral_pubkey'] ?? null,
                'iv' => $data['iv'] ?? null,
                'mac' => $data['mac'] ?? null,
                'message_id' => $data['message_id'],
                'ttl_expires_at' => now()->addSeconds($ttlSeconds),
            ]);
        });
    }

    public function history(int $conversationId, int $userId, int $limit = 100, ?string $before = null)
    {
        $query = Message::where('conversation_id', $conversationId)
            ->where('is_deleted', false)
            ->where(function ($q) use ($before) {
                if ($before) {
                    $q->where('created_at', '<', $before);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        return $query->get()->map(fn ($m) => $m->toPublicArray())->reverse()->values();
    }

    public function softDelete(int $messageId, int $userId): bool
    {
        return Message::where('id', $messageId)->update(['is_deleted' => true]) > 0;
    }

    public function purgeExpired(): int
    {
        return Message::where('ttl_expires_at', '<', now())->delete();
    }
}