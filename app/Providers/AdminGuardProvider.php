<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;

class AdminGuardProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Auth::extend('admin', function ($app, $name, array $config) {
            return new class($app['request'], $app['auth']->createUserProvider($config['provider'])) {
                public function __construct(private $request, private $provider) {}

                public function user()
                {
                    $token = $this->request->bearerToken();
                    if (!$token) return null;

                    try {
                        $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();
                        $adminId = $payload['sub'] ?? null;
                        if (!$adminId) return null;

                        return Admin::find($adminId);
                    } catch (\Throwable $e) {
                        return null;
                    }
                }

                public function check()
                {
                    return $this->user() !== null;
                }

                public function guest()
                {
                    return !$this->check();
                }

                public function id()
                {
                    return $this->user()?->id;
                }

                public function validateCredentials($credentials)
                {
                    return false;
                }

                public function login($user, $remember = false)
                {
                    return \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
                }

                public function logout()
                {
                    \Tymon\JWTAuth\Facades\JWTAuth::invalidate(\Tymon\JWTAuth\Facades\JWTAuth::getToken());
                }
            };
        });
    }
}