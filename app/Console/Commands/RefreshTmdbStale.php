<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RefreshTmdbMovieJob;
use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshTmdbStale extends Command
{
    protected $signature = 'tmdb:refresh-stale
                            {--limit=50 : Maximum number of movies to queue}
                            {--force-backfill : Only movies with tmdb_last_synced_at null}
                            {--force : Ignore cadence; refresh all with tmdb_id (admin hard refresh)}
                            {--dry-run : List movies that would be refreshed without dispatching jobs}';

    protected $description = 'Queue TMDB refresh jobs for stale movies (cadence + max-age)';

    public function handle(): int
    {
        $start = now();
        $limit = (int) $this->option('limit');
        $forceBackfill = $this->option('force-backfill');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $movies = $this->resolveCandidates($limit, $force, $forceBackfill);

        if ($movies->isEmpty()) {
            $this->info('No movies to refresh.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->table(
                ['ID', 'Title', 'Last synced'],
                $movies->map(fn (Movie $m) => [
                    $m->id,
                    $m->title,
                    $m->tmdb_last_synced_at?->toDateTimeString() ?? 'never',
                ])->all()
            );
            $this->info("Dry run: {$movies->count()} movie(s) would be refreshed.");

            return self::SUCCESS;
        }

        foreach ($movies as $movie) {
            RefreshTmdbMovieJob::dispatch($movie);
        }

        $duration = $start->diffInSeconds(now());
        $count = $movies->count();

        Log::info('tmdb:refresh-stale completed', [
            'jobs_dispatched' => $count,
            'duration_seconds' => $duration,
        ]);

        $this->info("Dispatched {$count} refresh job(s) in {$duration}s.");

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Movie>
     */
    private function resolveCandidates(int $limit, bool $force, bool $forceBackfill): \Illuminate\Support\Collection
    {
        if ($force) {
            return Movie::query()
                ->whereNotNull('tmdb_id')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        if ($forceBackfill) {
            return Movie::query()
                ->whereNotNull('tmdb_id')
                ->whereNull('tmdb_last_synced_at')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        return Movie::query()
            ->staleForTmdbRefresh($limit)
            ->get();
    }
}
