<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Movie;
use App\Services\DashboardStatsService;
use App\Services\MovieFilterService;

class MovieObserver
{
    public function __construct(
        protected MovieFilterService $filterService,
        protected DashboardStatsService $statsService
    ) {}

    /**
     * Handle the Movie "created" event.
     */
    public function created(Movie $movie): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Movie "updated" event.
     */
    public function updated(Movie $movie): void
    {
        // Check if relevant fields changed
        $relevantFields = ['status', 'country', 'conflict', 'release_year', 'is_upcoming'];
        $changedFields = array_keys($movie->getChanges());

        if (array_intersect($relevantFields, $changedFields)) {
            $this->clearCaches();
        }
    }

    /**
     * Handle the Movie "deleted" event.
     */
    public function deleted(Movie $movie): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Movie "restored" event.
     */
    public function restored(Movie $movie): void
    {
        $this->clearCaches();
    }

    /**
     * Handle the Movie "force deleted" event.
     */
    public function forceDeleted(Movie $movie): void
    {
        $this->clearCaches();
    }

    /**
     * Clear all relevant caches.
     */
    protected function clearCaches(): void
    {
        $this->filterService->clearCache();
        $this->statsService->clearCache();
    }
}
