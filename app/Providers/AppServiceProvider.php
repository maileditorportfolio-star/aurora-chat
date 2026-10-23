<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Http\Controllers\AuthController::class,
            function ($app) {
                return new \App\Http\Controllers\AuthController(
                    $app->make(\App\Services\AuthService::class)
                );
            }
        );

        $this->app->bind(
            \App\Http\Controllers\ConversationController::class,
            function ($app) {
                return new \App\Http\Controllers\ConversationController(
                    $app->make(\App\Services\ConversationService::class)
                );
            }
        );

        $this->app->bind(
            \App\Http\Controllers\MessageController::class,
            function ($app) {
                return new \App\Http\Controllers\MessageController(
                    $app->make(\App\Services\MessageService::class)
                );
            }
        );

        $this->app->bind(
            \App\Http\Controllers\ReportController::class,
            function ($app) {
                return new \App\Http\Controllers\ReportController(
                    $app->make(\App\Services\ReportService::class)
                );
            }
        );

        $this->app->bind(
            \App\Http\Controllers\UserController::class,
            function ($app) {
                return new \App\Http\Controllers\UserController(
                    $app->make(\App\Services\ShortIdService::class)
                );
            }
        );
    }

    public function boot(): void
    {
        //
    }
}