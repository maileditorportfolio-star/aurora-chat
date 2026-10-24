<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConversationService
{
    public function __construct()
    {
    }

    public function createDM(int $userAId, int $userBId): Conversation
    {
        return DB::transaction(function () use ($userAId, $userBId) {
            $existing = ConversationMember::whereHas('conversation', function ($q) use ($userAId, $userBId) {
                $q->whereHas('members', function ($m) use ($userAId) {
                    $m->where('user_id', $userAId);
                })->whereHas('members', function ($m) use ($userBId) {
                    $m->where('user_id', $userBId);
                })->where('type', 'dm');
            })->first();

            if ($existing) {
                return $existing->conversation;
            }

            $conv = Conversation::create([
                'uuid' => (string) Str::uuid(),
                'type' => 'dm',
                'title' => null,
            ]);

            ConversationMember::insert([
                ['conversation_id' => $conv->id, 'user_id' => $userAId, 'role' => 'member', 'joined_at' => now()],
                ['conversation_id' => $conv->id, 'user_id' => $userBId, 'role' => 'member', 'joined_at' => now()],
            ]);

            return $conv->load('members.user');
        });
    }

    public function createGroup(int $ownerId, array $memberIds, ?string $title): Conversation
    {
        return DB::transaction(function () use ($ownerId, $memberIds, $title) {
            $conv = Conversation::create([
                'uuid' => (string) Str::uuid(),
                'type' => 'group',
                'title' => $title,
            ]);

            $members = [];
            $members[] = ['conversation_id' => $conv->id, 'user_id' => $ownerId, 'role' => 'owner', 'joined_at' => now()];

            foreach ($memberIds as $id) {
                if ((int)$id === $ownerId) continue;
                $members[] = ['conversation_id' => $conv->id, 'user_id' => $id, 'role' => 'member', 'joined_at' => now()];
            }

            ConversationMember::insert($members);
            return $conv->load('members.user');
        });
    }

    public function addMember(int $convId, int $userId, string $role = 'member'): ConversationMember
    {
        $existing = ConversationMember::where('conversation_id', $convId)->where('user_id', $userId)->first();
        if ($existing) return $existing;

        return ConversationMember::create([
            'conversation_id' => $convId,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function getUserConversations(int $userId)
    {
        return ConversationMember::where('user_id', $userId)
            ->with(['conversation.members.user'])
            ->orderBy('joined_at', 'desc')
            ->get()
            ->map(function (ConversationMember $m) {
                $conv = $m->conversation;
                $otherMembers = $conv->members->where('user_id', '!=', $userId);
                $conv->display_title = $conv->type === 'dm'
                    ? ($otherMembers->first()?->user?->name ?? 'Unknown')
                    : ($conv->title ?? 'Group ' . $conv->id);
                $conv->last_message_at = $conv->messages->max('created_at');
                $conv->unread_count = 0;
                return $conv;
            });
    }
}