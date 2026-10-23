<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Middleware\HandleCors;

class CorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app['router']->pushMiddlewareToGroup('api', HandleCors::class);
        $this->app['router']->pushMiddlewareToGroup('web', HandleCors::class);
    }
}
