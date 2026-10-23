<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    public function __construct(private ConversationService $convService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $convs = $this->convService->getUserConversations($user->id);
        return response()->json(['success' => true, 'data' => $convs]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:dm,group',
            'title' => 'nullable|string|max:120',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        $user = $request->user();
        $memberIds = array_unique(array_map('intval', $data['member_ids']));

        if (!in_array($user->id, $memberIds)) {
            $memberIds[] = $user->id;
        }

        try {
            if ($data['type'] === 'dm') {
                if (count($memberIds) > 2) {
                    return response()->json(['success' => false, 'message' => 'DM can only have 2 members'], 400);
                }
                $conv = $this->convService->createDM($memberIds[0], $memberIds[1]);
            } else {
                $conv = $this->convService->createGroup($user->id, $memberIds, $data['title'] ?? null);
            }
        } catch (\Throwable $e) {
            Log::error('Conversation creation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create conversation'], 500);
        }

        return response()->json(['success' => true, 'data' => $conv], 201);
    }

    public function addMember(Request $request, int $conversationId): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'sometimes|in:member,admin,owner',
        ]);

        $conv = Conversation::find($conversationId);
        if (!$conv) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $member = $this->convService->addMember($conversationId, $data['user_id'], $data['role'] ?? 'member');
        return response()->json(['success' => true, 'data' => $member->load('user')], 201);
    }
}