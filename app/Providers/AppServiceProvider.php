<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\LinkArticlesToMovies;
use App\Models\Movie;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Observers\MovieObserver;
use App\Observers\ReviewCommentObserver;
use App\Policies\MoviePolicy;
use App\Policies\ReviewCommentPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use JonesRussell\NorthCloud\Events\ArticleProcessed;

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
        ReviewComment::observe(ReviewCommentObserver::class);
    }

    /**
     * Register model policies.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Movie::class, MoviePolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(ReviewComment::class, ReviewCommentPolicy::class);
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
