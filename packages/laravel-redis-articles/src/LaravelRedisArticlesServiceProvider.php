<?php

namespace JonesRussell\LaravelRedisArticles;

use Illuminate\Support\ServiceProvider;
use JonesRussell\LaravelRedisArticles\Console\Commands\SubscribeToArticleFeed;

class LaravelRedisArticlesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/redis-articles.php',
            'redis-articles'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SubscribeToArticleFeed::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/redis-articles.php' => config_path('redis-articles.php'),
            ], 'redis-articles-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'redis-articles-migrations');

            $this->publishes([
                __DIR__.'/../database/factories' => database_path('factories'),
            ], 'redis-articles-factories');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
