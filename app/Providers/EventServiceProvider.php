<?php

namespace App\Providers;

use App\Events\ProductImageDeleted;
use App\Listeners\DeleteFromCloudinary;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    protected array $listen = [
        ProductImageDeleted::class => [
            DeleteFromCloudinary::class,
        ],
    ];

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
