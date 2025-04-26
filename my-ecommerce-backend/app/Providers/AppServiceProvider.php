<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\JWT;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // ✅ Force JWT Binding
        $this->app->bind(JWTAuth::class, function ($app) {
            return $app->make('tymon.jwt.auth');
        });

        $this->app->bind(JWT::class, function ($app) {
            return $app->make('tymon.jwt');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
