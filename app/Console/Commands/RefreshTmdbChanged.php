<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RefreshTmdbMovieJob;
use App\Models\Movie;
use App\Services\TMDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshTmdbChanged extends Command
{
    protected $signature = 'tmdb:refresh-changed
                            {--limit= : Maximum number of refresh jobs to dispatch (default: no limit)}
                            {--days= : Number of days to query TMDB changes (overrides config)}';

    protected $description = 'Queue TMDB refresh jobs for movies that changed on TMDB (uses /movie/changes API)';

    public function handle(TMDBService $tmdb): int
    {
        if (! config('tmdb.api_key')) {
            $this->error('TMDB API key not configured.');

            return self::FAILURE;
        }

        $days = (int) ($this->option('days') ?? config('tmdb.import.changes_days_back', 1));
        $days = max(1, min(14, $days));
        $limitOption = $this->option('limit');
        $limit = $limitOption !== null ? (int) $limitOption : null;

        $endDate = now()->toDateString();
        $startDate = now()->subDays($days)->toDateString();

        $this->info("Fetching movie IDs changed between {$startDate} and {$endDate}...");

        $changedIds = $tmdb->getMovieChangeIds($startDate, $endDate);

        if (empty($changedIds)) {
            $this->info('No changed movies reported by TMDB.');

            return self::SUCCESS;
        }

        $movies = Movie::query()
            ->whereNotNull('tmdb_id')
            ->whereIn('tmdb_id', $changedIds)
            ->orderBy('id')
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();

        if ($movies->isEmpty()) {
            $this->info('None of the changed movies are in our database.');

            return self::SUCCESS;
        }

        foreach ($movies as $movie) {
            RefreshTmdbMovieJob::dispatch($movie);
        }

        $count = $movies->count();
        Log::info('tmdb:refresh-changed completed', [
            'jobs_dispatched' => $count,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $this->info("Dispatched {$count} refresh job(s) for movies that changed on TMDB.");

        return self::SUCCESS;
    }
}
