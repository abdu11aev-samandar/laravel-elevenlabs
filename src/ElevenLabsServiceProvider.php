<?php

namespace Samandar\LaravelElevenLabs;

use Illuminate\Support\ServiceProvider;
use Samandar\LaravelElevenLabs\Services\ElevenLabsService;

class ElevenLabsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/elevenlabs.php', 'elevenlabs');

        // Register the service
        $this->app->singleton(ElevenLabsService::class, function ($app) {
            $apiKey = config('elevenlabs.api_key');
            
            if (empty($apiKey)) {
                throw new \InvalidArgumentException('ElevenLabs API key is not configured. Please set ELEVENLABS_API_KEY in your .env file.');
            }

            return new ElevenLabsService($apiKey);
        });

        // Register the facade alias
        $this->app->alias(ElevenLabsService::class, 'elevenlabs');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/elevenlabs.php' => config_path('elevenlabs.php'),
            ], 'elevenlabs-config');
        }

        // Load routes if they exist
        if (file_exists(__DIR__ . '/routes/web.php')) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }

        // Load views if they exist
        if (is_dir(__DIR__ . '/../resources/views')) {
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'elevenlabs');
        }

        // Publish assets
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/elevenlabs'),
            ], 'elevenlabs-views');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ElevenLabsService::class,
            'elevenlabs',
        ];
    }
}
