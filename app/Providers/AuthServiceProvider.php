<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Horizon\Horizon;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only allow access to Horizon if the user is an admin
        Horizon::auth(function ($request) {
            if (auth('admin_tools')->check()) {
                return true;
            }
            throw new UnauthorizedHttpException('You are not authorized to access this resource.');
        });

    }
}
