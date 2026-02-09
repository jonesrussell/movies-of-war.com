<?php

declare(strict_types=1);

namespace JonesRussell\NorthcloudLaravel;

use Illuminate\Support\ServiceProvider;
use JonesRussell\NorthcloudLaravel\Console\Commands\SubscribeToArticleFeed;

class NorthcloudLaravelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/northcloud.php',
            'northcloud'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SubscribeToArticleFeed::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/northcloud.php' => config_path('northcloud.php'),
            ], 'northcloud-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'northcloud-migrations');

            $this->publishes([
                __DIR__.'/../database/factories' => database_path('factories'),
            ], 'northcloud-factories');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
