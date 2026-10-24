<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['success' => true, 'data' => ['status' => 'ok', 'service' => 'aurora-chat-api', 'version' => '1.0.0']]);
});

// Auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::post('/auth/public-key', [AuthController::class, 'publicKey'])->middleware('auth:api');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:api');

// Users
Route::get('/users/by-id/{shortId}', [UserController::class, 'showByShortId']);
Route::get('/users/search', [UserController::class, 'search']);

// Conversations
Route::get('/conversations', [ConversationController::class, 'index'])->middleware('auth:api');
Route::post('/conversations', [ConversationController::class, 'store'])->middleware('auth:api');
Route::post('/conversations/{conversationId}/members', [ConversationController::class, 'addMember'])->middleware('auth:api');

// Messages
Route::post('/messages', [MessageController::class, 'store'])->middleware('auth:api');
Route::get('/messages/{conversationId}', [MessageController::class, 'history'])->middleware('auth:api');
Route::delete('/messages/{messageId}', [MessageController::class, 'delete'])->middleware('auth:api');

// Reports
Route::post('/reports', [ReportController::class, 'store'])->middleware('auth:api');

// Contact
Route::post('/contact', [ContactController::class, 'store']);

// Admin
Route::post('/admin/login', [AdminController::class, 'login']);
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/me', [AdminController::class, 'me']);
    Route::get('/admin/emails', [AdminController::class, 'emails']);
    Route::get('/admin/blocked', [AdminController::class, 'blockedUsers']);
    Route::get('/admin/submissions', [AdminController::class, 'submissions']);
    Route::post('/admin/submissions/{id}/read', [AdminController::class, 'markRead']);
    Route::get('/admin/stats', [AdminController::class, 'stats']);
});

// Debug
Route::post('/debug-body', function (Request $request) {
    return response()->json([
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method(),
        'body' => file_get_contents('php://input'),
        'all' => $request->all(),
        'json' => $request->json()->all(),
        'is_json' => $request->isJson(),
    ]);
});