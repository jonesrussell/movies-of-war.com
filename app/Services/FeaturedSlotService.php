<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SelectionMethod;
use App\Enums\SlotStrategy;
use App\Enums\SlotType;
use App\Models\FeaturedSlotHistory;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeaturedSlotService
{
    private const QUEUE_DEPTH = 4;

    private const SLOTS = [SlotType::PickOfWeek, SlotType::Hero];

    /**
     * Get eligible movies using the PickOfWeek strategy (highest-rated).
     * Kept for backward compatibility; prefer getEligibleMoviesForSlot().
     *
     * @return Collection<int, Movie>
     */
    public function getEligibleMovies(): Collection
    {
        return $this->getEligibleMoviesForSlot(SlotType::PickOfWeek);
    }

    /**
     * Get eligible movies for a specific slot, applying that slot's strategy.
     *
     * @return Collection<int, Movie>
     */
    public function getEligibleMoviesForSlot(SlotType $slot): Collection
    {
        $query = $this->baseEligibleQuery();

        return match ($slot->strategy()) {
            SlotStrategy::Upcoming => $this->applyUpcomingStrategy($query),
            SlotStrategy::HighestRated => $this->applyHighestRatedStrategy($query),
        };
    }

    /**
     * Fill the queue to QUEUE_DEPTH weeks ahead for each slot.
     *
     * @return int Number of entries added
     */
    public function fillQueue(): int
    {
        $added = 0;

        foreach (self::SLOTS as $slot) {
            $existing = FeaturedSlotQueue::slot($slot)->count();
            $needed = self::QUEUE_DEPTH - $existing;

            if ($needed <= 0) {
                continue;
            }

            $lastScheduled = FeaturedSlotQueue::slot($slot)
                ->orderByDesc('scheduled_for')
                ->value('scheduled_for');

            $nextSunday = $lastScheduled
                ? Carbon::parse($lastScheduled)->next('Sunday')
                : $this->nextSunday();

            for ($i = 0; $i < $needed; $i++) {
                $scheduledFor = $nextSunday->copy()->addWeeks($i);
                if ($this->assignMovieToSlot($slot, $scheduledFor)) {
                    $added++;
                }
            }
        }

        return $added;
    }

    /**
     * Insert a manual queue entry at a given position, shifting existing entries down.
     * Wrapped in a transaction to prevent position corruption.
     */
    public function insertAtPosition(SlotType $slot, int $movieId, int $position): void
    {
        DB::transaction(function () use ($slot, $movieId, $position): void {
            FeaturedSlotQueue::slot($slot)
                ->where('position', '>=', $position)
                ->orderByDesc('position')
                ->get()
                ->each(function (FeaturedSlotQueue $entry): void {
                    $entry->update(['position' => $entry->position + 1]);
                });

            $scheduledFor = $this->calculateScheduledFor($slot, $position);

            FeaturedSlotQueue::create([
                'movie_id' => $movieId,
                'slot' => $slot,
                'position' => $position,
                'selection_method' => SelectionMethod::Manual,
                'scheduled_for' => $scheduledFor->toDateString(),
            ]);
        });
    }

    /**
     * Remove a queue entry and reindex remaining positions.
     * Wrapped in a transaction to prevent position corruption.
     */
    public function removeAndReindex(FeaturedSlotQueue $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $slot = $entry->slot;
            $position = $entry->position;

            $entry->delete();

            FeaturedSlotQueue::slot($slot)
                ->where('position', '>', $position)
                ->orderBy('position')
                ->get()
                ->each(function (FeaturedSlotQueue $entry): void {
                    $entry->update(['position' => $entry->position - 1]);
                });
        });
    }

    /**
     * Reindex all positions for a slot after consuming the first entry.
     */
    public function reindexPositions(SlotType $slot): void
    {
        FeaturedSlotQueue::slot($slot)
            ->orderBy('position')
            ->get()
            ->each(function (FeaturedSlotQueue $entry, int $index): void {
                $entry->update(['position' => $index + 1]);
            });
    }

    /**
     * Base query for eligible movies: published, not queued, with catalog exhaustion handling.
     *
     * @return Builder<Movie>
     */
    private function baseEligibleQuery(): Builder
    {
        $publishedCount = Movie::query()->published()->count();
        $featuredMovieIds = FeaturedSlotHistory::query()
            ->distinct()
            ->pluck('movie_id')
            ->filter()
            ->toArray();

        $exhausted = $publishedCount > 0 && count($featuredMovieIds) >= $publishedCount;

        $queuedMovieIds = FeaturedSlotQueue::query()->pluck('movie_id')->toArray();

        $query = Movie::query()
            ->published()
            ->whereNotIn('id', $queuedMovieIds);

        if (! $exhausted) {
            $query->whereNotIn('id', $featuredMovieIds);
        }

        return $query;
    }

    /**
     * Upcoming strategy: upcoming movies by soonest release date, falls back to highest-rated.
     *
     * @param  Builder<Movie>  $query
     * @return Collection<int, Movie>
     */
    private function applyUpcomingStrategy(Builder $query): Collection
    {
        $upcomingQuery = clone $query;
        $upcoming = $upcomingQuery
            ->upcoming()
            ->orderByRaw('CASE WHEN release_date IS NULL THEN 1 ELSE 0 END, release_date ASC')
            ->orderByDesc('tmdb_vote_average')
            ->limit(20)
            ->get();

        if ($upcoming->isNotEmpty()) {
            return $upcoming;
        }

        return $this->applyHighestRatedStrategy($query);
    }

    /**
     * Highest-rated strategy: order by TMDB vote average descending.
     *
     * @param  Builder<Movie>  $query
     * @return Collection<int, Movie>
     */
    private function applyHighestRatedStrategy(Builder $query): Collection
    {
        return $query
            ->orderByDesc('tmdb_vote_average')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    /**
     * Pick the next eligible movie for a slot on a given date and insert it.
     */
    private function assignMovieToSlot(SlotType $slot, Carbon $scheduledFor): bool
    {
        $otherSlotMovieId = FeaturedSlotQueue::query()
            ->where('scheduled_for', $scheduledFor->toDateString())
            ->where('slot', '!=', $slot->value)
            ->value('movie_id');

        $eligible = $this->getEligibleMoviesForSlot($slot);

        if ($otherSlotMovieId) {
            $eligible = $eligible->where('id', '!=', $otherSlotMovieId);
        }

        $movie = $eligible->first();
        if (! $movie) {
            Log::warning('No eligible movie for featured slot assignment', [
                'slot' => $slot->value,
                'scheduled_for' => $scheduledFor->toDateString(),
                'published_count' => Movie::query()->published()->count(),
            ]);

            return false;
        }

        $nextPosition = (FeaturedSlotQueue::slot($slot)->max('position') ?? 0) + 1;

        FeaturedSlotQueue::create([
            'movie_id' => $movie->id,
            'slot' => $slot,
            'position' => $nextPosition,
            'selection_method' => SelectionMethod::Auto,
            'scheduled_for' => $scheduledFor->toDateString(),
        ]);

        return true;
    }

    /**
     * Calculate scheduled_for date based on existing queue entries for a slot.
     */
    private function calculateScheduledFor(SlotType $slot, int $position): Carbon
    {
        $firstScheduled = FeaturedSlotQueue::slot($slot)
            ->orderBy('position')
            ->value('scheduled_for');

        if ($firstScheduled) {
            return Carbon::parse($firstScheduled)->addWeeks($position - 1);
        }

        return $this->nextSunday()->addWeeks($position - 1);
    }

    private function nextSunday(): Carbon
    {
        $now = Carbon::now();

        return $now->isSunday() ? $now->copy() : $now->next('Sunday');
    }
}
