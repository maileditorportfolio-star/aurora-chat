<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:128',
            'public_key' => 'nullable|string',
        ]);

        try {
            $result = $this->authService->register($data);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'bearer',
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->authService->login($data);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'bearer',
            ],
        ]);
    }

    public function google(Request $request): JsonResponse
    {
        $data = $request->validate([
            'google_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
            'avatar' => 'nullable|string',
        ]);

        try {
            $result = $this->authService->googleLogin(
                $data['google_id'], $data['email'], $data['name'], $data['avatar'] ?? null
            );
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'bearer',
            ],
        ]);
    }

    public function publicKey(Request $request): JsonResponse
    {
        $data = $request->validate(['public_key' => 'required|string']);
        $user = $request->user();

        $updated = $this->authService->updatePublicKey($user->id, $data['public_key']);

        return response()->json(['success' => true, 'data' => $updated->toPublicArray()]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json(['success' => true, 'data' => $user->toPublicArray()]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();
        return response()->json(['success' => true, 'message' => 'Logged out']);
    }
}