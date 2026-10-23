<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = \App\Models\Admin::where('email', $data['email'])->first();
        if (!$admin || !\Illuminate\Support\Facades\Hash::check($data['password'], $admin->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $token = auth()->guard('admin')->login($admin);
        return response()->json(['success' => true, 'data' => ['token' => $token, 'token_type' => 'bearer', 'admin' => ['id' => $admin->id, 'email' => $admin->email, 'name' => $admin->name]]]);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user('admin');
        return response()->json(['success' => true, 'data' => ['id' => $admin->id, 'email' => $admin->email, 'name' => $admin->name]]);
    }

    public function emails(Request $request): JsonResponse
    {
        $users = User::select('id', 'short_id', 'email', 'name', 'created_at', 'is_blocked', 'report_count', 'avatar_url')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => ['total' => $users->total(), 'current_page' => $users->currentPage(), 'last_page' => $users->lastPage()],
        ]);
    }

    public function blockedUsers(): JsonResponse
    {
        $users = User::where('is_blocked', true)
            ->select('id', 'short_id', 'email', 'name', 'blocked_at', 'report_count')
            ->withCount('reportsReceived')
            ->orderBy('blocked_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function submissions(): JsonResponse
    {
        $submissions = ContactSubmission::with('user:id,short_id,email,name')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'meta' => ['total' => $submissions->total(), 'current_page' => $submissions->currentPage(), 'last_page' => $submissions->lastPage()],
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $s = ContactSubmission::find($id);
        if (!$s) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        $s->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'total_users' => User::count(),
            'blocked_users' => User::where('is_blocked', true)->count(),
            'total_messages' => \App\Models\Message::count(),
            'open_submissions' => ContactSubmission::where('is_read', false)->count(),
        ]]);
    }
}