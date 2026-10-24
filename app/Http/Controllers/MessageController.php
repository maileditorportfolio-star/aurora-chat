<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function __construct(private MessageService $messageService)
    {
    }

    private function getInput(Request $request): array
    {
        $rawBody = file_get_contents('php://input');
        if ($rawBody && $request->isJson()) {
            $fixed = str_replace(['\\:', '\\,'], [':', ','], $rawBody);
            $decoded = json_decode($fixed, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $request->all();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->getInput($request);
        $data = $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message_id' => 'required|string|max:36',
            'ciphertext' => 'required|string',
            'sender_ephemeral_pubkey' => 'nullable|string',
            'iv' => 'nullable|string',
            'mac' => 'nullable|string',
        ]);

        $data['sender_id'] = $request->user()->id;

        try {
            $message = $this->messageService->store($data);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'data' => $message->toPublicArray()], 201);
    }

    public function history(Request $request, int $conversationId): JsonResponse
    {
        $limit = min((int) $request->query('limit', 100), 200);
        $before = $request->query('before');
        $messages = $this->messageService->history($conversationId, $request->user()->id, $limit, $before);
        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function delete(Request $request, int $messageId): JsonResponse
    {
        $ok = $this->messageService->softDelete($messageId, $request->user()->id);
        return response()->json(['success' => $ok, 'message' => $ok ? 'Deleted' : 'Not found']);
    }
}