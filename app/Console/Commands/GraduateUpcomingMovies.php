<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RefreshTmdbMovieJob;
use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GraduateUpcomingMovies extends Command
{
    protected $signature = 'movies:graduate-upcoming
                            {--dry-run : List movies that would be graduated without making changes}';

    protected $description = 'Graduate upcoming movies to released when their release date has passed';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $today = now()->toDateString();

        $movies = Movie::query()
            ->where('is_upcoming', true)
            ->whereNotNull('release_date')
            ->where('release_date', '<', $today)
            ->orderBy('release_date')
            ->get();

        if ($movies->isEmpty()) {
            $this->info('No upcoming movies to graduate.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->table(
                ['ID', 'Title', 'Release Date'],
                $movies->map(fn (Movie $m) => [
                    $m->id,
                    $m->title,
                    $m->release_date?->toDateString(),
                ])->all()
            );
            $this->info("Dry run: {$movies->count()} movie(s) would be graduated.");

            return self::SUCCESS;
        }

        $refreshed = 0;
        $updated = 0;

        foreach ($movies as $movie) {
            if ($movie->tmdb_id !== null) {
                RefreshTmdbMovieJob::dispatch($movie);
                $refreshed++;
            } else {
                $movie->update(['is_upcoming' => false]);
                $updated++;
            }
        }

        Log::info('movies:graduate-upcoming completed', [
            'jobs_dispatched' => $refreshed,
            'direct_updates' => $updated,
            'total' => $movies->count(),
        ]);

        $this->info("Graduated {$movies->count()} movie(s): {$refreshed} queued for TMDB refresh, {$updated} updated directly.");

        return self::SUCCESS;
    }
}
