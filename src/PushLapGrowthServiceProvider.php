<?php

namespace PushLapGrowth;

use Illuminate\Support\ServiceProvider;

class PushLapGrowthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/pushlapgrowth.php' => config_path('pushlapgrowth.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pushlapgrowth.php',
            'pushlapgrowth'
        );

        $this->app->singleton(Client::class, function ($app) {
            return new Client($app['config']->get('pushlapgrowth.api_token'));
        });

        // Also register 'pushlapgrowth' alias if needed, but class name is cleaner.
        $this->app->alias(Client::class, 'pushlapgrowth');
    }
}
