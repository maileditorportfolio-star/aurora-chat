<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ShortIdService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private ShortIdService $shortId)
    {
    }

    public function showByShortId(Request $request, string $shortId): JsonResponse
    {
        $user = $this->shortId->resolve($shortId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $user->toPublicArray()]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        if (strlen($q) < 1) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $query = User::query()->where('is_blocked', false);

        if (preg_match('/^\d{3,5}$/', $q)) {
            $query->where('short_id', $q);
        } else {
            $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(20)->get()->map(fn ($u) => $u->toPublicArray()),
        ]);
    }
}