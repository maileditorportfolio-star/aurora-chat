<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\JWTAuth;

class AuthService
{
    public function __construct(
        private ShortIdService $shortId,
    ) {
    }

    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'public_key' => $data['public_key'] ?? null,
            'short_id' => $this->shortId->generate(),
        ]);

        $token = auth('api')->login($user);

        return ['user' => $user->toPublicArray(), 'token' => $token];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new \Illuminate\Validation\ValidationException(
                validator()->make([], [])->fails(),
                ['email' => ['The provided credentials are incorrect.']]
            );
        }

        if ($user->is_blocked) {
            throw new \Exception('Account is blocked. Contact support.', 403);
        }

        $token = auth('api')->login($user);
        return ['user' => $user->toPublicArray(), 'token' => $token];
    }

    public function googleLogin(string $googleId, string $email, string $name, ?string $avatar): array
    {
        $user = User::where('google_id', $googleId)->first();

        if (!$user) {
            $user = User::create([
                'google_id' => $googleId,
                'email' => $email,
                'name' => $name,
                'avatar_url' => $avatar,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        if ($user->is_blocked) {
            throw new \Exception('Account is blocked.', 403);
        }

        $token = auth('api')->login($user);
        return ['user' => $user->toPublicArray(), 'token' => $token];
    }

    public function updatePublicKey(int $userId, string $publicKey): User
    {
        $user = User::lockForUpdate()->find($userId);
        if (!$user) throw new \Exception('User not found', 404);
        $user->update(['public_key' => $publicKey]);
        return $user->fresh();
    }

    public function me(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) throw new \Exception('User not found', 404);
        return $user->toPublicArray();
    }
}