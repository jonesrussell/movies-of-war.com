<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeaturedSlotHistory;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FeaturedSlotService
{
    private const QUEUE_DEPTH = 4;

    private const SLOTS = ['pick_of_week', 'hero'];

    /**
     * Get eligible movies ranked by rating desc, created_at desc.
     * If all published movies have been featured, reset eligibility (catalog exhaustion).
     *
     * @return Collection<int, Movie>
     */
    public function getEligibleMovies(): Collection
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

        return $query
            ->orderByDesc('tmdb_vote_average')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Fill the queue to QUEUE_DEPTH weeks ahead for each slot.
     */
    public function fillQueue(): void
    {
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
                $this->assignMovieToSlot($slot, $scheduledFor);
            }
        }
    }

    /**
     * Pick the next eligible movie for a slot on a given date and insert it.
     */
    private function assignMovieToSlot(string $slot, Carbon $scheduledFor): void
    {
        $otherSlotMovieId = FeaturedSlotQueue::query()
            ->where('scheduled_for', $scheduledFor->toDateString())
            ->where('slot', '!=', $slot)
            ->value('movie_id');

        $eligible = $this->getEligibleMovies();

        if ($otherSlotMovieId) {
            $eligible = $eligible->where('id', '!=', $otherSlotMovieId);
        }

        $movie = $eligible->first();
        if (! $movie) {
            return;
        }

        $nextPosition = (FeaturedSlotQueue::slot($slot)->max('position') ?? 0) + 1;

        FeaturedSlotQueue::create([
            'movie_id' => $movie->id,
            'slot' => $slot,
            'position' => $nextPosition,
            'selection_method' => 'auto',
            'scheduled_for' => $scheduledFor->toDateString(),
        ]);
    }

    private function nextSunday(): Carbon
    {
        $now = Carbon::now();

        return $now->isSunday() ? $now->copy() : $now->next('Sunday');
    }
}
