<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading globally
        Model::preventLazyLoading();

        // Report lazy loading violations differently based on environment
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            $exception = new LazyLoadingViolationException($model, $relation);

            if ($this->app->isProduction()) {
                report($exception);

                return null;
            } else {
                throw $exception;
            }
        });

        // Prevent setting wrong attributes globally
        Model::preventSilentlyDiscardingAttributes();
        // Prevent accessing missing attributes globally
        Model::preventAccessingMissingAttributes();

        \DB::listen(function ($q) {
            logger()->debug('[SQL]', [
                'time_ms' => $q->time,
                'sql' => $q->sql,
                'bindings' => $q->bindings,
            ]);
        });
    }
}
