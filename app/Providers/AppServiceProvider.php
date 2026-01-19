<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\LinkArticlesToMovies;
use App\Models\Movie;
use App\Models\XPost;
use App\Observers\MovieObserver;
use App\Policies\MoviePolicy;
use App\Policies\XPostPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->registerPolicies();
        $this->registerEvents();
        $this->registerObservers();
    }

    /**
     * Register model observers.
     */
    private function registerObservers(): void
    {
        Movie::observe(MovieObserver::class);
    }

    /**
     * Register model policies.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Movie::class, MoviePolicy::class);
        Gate::policy(XPost::class, XPostPolicy::class);
    }

    /**
     * Register event listeners.
     */
    private function registerEvents(): void
    {
        Event::listen(
            ArticleProcessed::class,
            LinkArticlesToMovies::class
        );
    }
}
