<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Client;

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

        // Configure Meilisearch index settings with caching
        Cache::rememberForever('meili_products_index_settings_v1', function () {
            $host = config('scout.meilisearch.host');
            $key = config('scout.meilisearch.key');

            if (! $host) {
                return true; // Skip if no meili is configured
            }

            $client = new Client($host, $key);
            $index = $client->index('products');

            $index->updateSettings([
                'filterableAttributes' => ['category', 'name', 'status', 'variant_colors', 'variant_sizes', 'min_price', 'max_price'],
                'sortableAttributes' => ['price', 'min_price', 'max_price', 'name'],
            ]);

            return true;
        });
    }
}
