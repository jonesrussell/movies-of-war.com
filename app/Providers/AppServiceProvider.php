<?php

namespace App\Providers;

use App\Listeners\LinkArticlesToMovies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use JonesRussell\LaravelRedisArticles\Events\ArticleProcessed;

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
        Event::listen(
            ArticleProcessed::class,
            LinkArticlesToMovies::class
        );
    }
}
