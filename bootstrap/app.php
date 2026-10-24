<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware - HandleCors must be early for CORS headers
        // FixCorruptedJson must run early to fix LiteSpeed-mod_security JSON body corruption
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\FixCorruptedJson::class,
        ]);
        
        // Web middleware
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withProviders([
        \Tymon\JWTAuth\Providers\LaravelServiceProvider::class,
        \App\Providers\AppServiceProvider::class,
        \App\Providers\AdminGuardProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();