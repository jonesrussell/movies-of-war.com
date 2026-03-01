<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ImportTmdbMovieJob;
use App\Models\Movie;
use App\Services\TmdbImportCandidateFilter;
use App\Services\TmdbImportGapFiller;
use App\Services\TmdbMovieImportService;
use App\Services\TMDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ImportTmdbMovies extends Command
{
    protected $signature = 'tmdb:import
                            {--limit=30 : Number of movies to import}
                            {--upcoming : Only upcoming releases}
                            {--force : Re-import existing movies}
                            {--sync : Run import synchronously (default: dispatch jobs)}
                            {--no-gap-fill : Disable gap-filling (use default strategy order)}
                            {--page=1 : Starting page (legacy strategy only)}
                            {--random : Randomize candidate order before import}
                            {--debug : Show debug output}';

    protected $description = 'Import war movies from The Movie Database (TMDB) using intelligent multi-strategy discovery';

    protected TMDBService $tmdb;

    protected TmdbImportCandidateFilter $filter;

    protected TmdbImportGapFiller $gapFiller;

    protected TmdbMovieImportService $importService;

    protected bool $debug = false;

    public function handle(): int
    {
        if (! config('tmdb.api_key')) {
            $this->error('TMDB API key not configured. Please set TMDB_API_KEY in your .env file.');
            $this->info('Get your free API key at: https://www.themoviedb.org/settings/api');

            return self::FAILURE;
        }

        $this->tmdb = app(TMDBService::class);
        $this->filter = app(TmdbImportCandidateFilter::class);
        $this->gapFiller = app(TmdbImportGapFiller::class);
        $this->importService = app(TmdbMovieImportService::class);

        $limit = (int) $this->option('limit');
        $upcoming = (bool) $this->option('upcoming');
        $force = (bool) $this->option('force');
        $sync = (bool) $this->option('sync');
        $noGapFill = (bool) $this->option('no-gap-fill');
        $this->debug = (bool) $this->option('debug');

        $upcomingInfo = $upcoming ? ' (upcoming only)' : '';
        $syncInfo = $sync ? ' [SYNC MODE]' : ' [JOB MODE]';
        $gapInfo = $noGapFill ? ' [NO GAP-FILL]' : '';
        $debugInfo = $this->debug ? ' [DEBUG]' : '';
        $random = (bool) $this->option('random');
        $this->info("Discovering war movies from TMDB (limit: {$limit}){$upcomingInfo}{$syncInfo}{$gapInfo}{$debugInfo}...");

        $candidateIds = $this->discoverCandidateIds($limit, $upcoming, $force, $noGapFill, $random);

        $this->info("Found {$candidateIds->count()} candidate movies after filtering.");

        if ($candidateIds->isEmpty()) {
            $this->warn('No candidates to import.');

            return self::SUCCESS;
        }

        if ($sync) {
            return $this->importSync($candidateIds, $force);
        } else {
            return $this->importAsync($candidateIds, $force);
        }
    }

    /**
     * Discover candidate movie IDs using multi-strategy discovery, fill-to-limit, and filtering.
     * Keeps fetching pages until at least $limit new (not already in DB) candidates or sources exhausted.
     *
     * @return Collection<int, int>
     */
    protected function discoverCandidateIds(int $limit, bool $upcoming, bool $force, bool $noGapFill, bool $random = false): Collection
    {
        $strategies = config('tmdb.import.discovery_strategies', []);

        if (empty($strategies)) {
            return $this->discoverWithLegacyStrategy($limit, $upcoming);
        }

        if (! $noGapFill) {
            $strategies = $this->gapFiller->sortStrategiesByGap($strategies);
        }

        $allResults = collect();
        $strategyPages = [];
        foreach ($strategies as $strategy) {
            $key = $strategy['key'] ?? 'unknown';
            $strategyPages[$key] = 1;
        }
        $maxRounds = 100;
        $round = 0;
        $ids = collect();

        while ($round < $maxRounds) {
            $round++;
            $anyNew = false;

            foreach ($strategies as $strategy) {
                $key = $strategy['key'] ?? 'unknown';
                $page = $strategyPages[$key] ?? 1;
                $pageResults = $this->fetchStrategyResultsOnePage($strategy, $page, $upcoming);

                if ($pageResults->isEmpty()) {
                    continue;
                }

                $anyNew = true;
                $seenIds = $allResults->pluck('id')->flip()->all();
                foreach ($pageResults as $movie) {
                    $id = $movie['id'] ?? null;
                    if ($id !== null && ! isset($seenIds[$id])) {
                        $seenIds[$id] = true;
                        $allResults->push($movie);
                    }
                }
                $strategyPages[$key] = $page + 1;
                usleep(250000);
            }

            $candidates = $this->filter->filter($allResults, $upcoming)->values();
            $ids = $candidates;

            if (! $force) {
                $existingTmdbIds = Movie::whereIn('tmdb_id', $ids->all())->pluck('tmdb_id');
                $ids = $ids->reject(fn (int $id) => $existingTmdbIds->contains($id))->values();
            }

            if ($ids->count() >= $limit) {
                $ids = $ids->take($limit)->values();
                if ($random) {
                    $ids = $ids->shuffle()->values();
                }
                Log::info('TMDB import discovery fill-to-limit', ['rounds' => $round, 'new_count' => $ids->count()]);

                return $ids;
            }

            if (! $anyNew) {
                break;
            }
        }

        $ids = $ids->take($limit)->values();
        if ($random) {
            $ids = $ids->shuffle()->values();
        }

        return $ids;
    }

    /**
     * Fetch one page of results for a strategy (genre, keywords, popular, or trending).
     * Popular and trending are skipped when $upcoming is true.
     *
     * @param  array<string, mixed>  $strategy
     * @return Collection<int, array<string, mixed>>
     */
    protected function fetchStrategyResultsOnePage(array $strategy, int $page, bool $upcoming): Collection
    {
        $type = $strategy['type'] ?? '';

        if ($type === 'popular') {
            if ($upcoming) {
                return collect();
            }

            return $this->tmdb->getPopularMoviesAsDto($page)->results;
        }

        if ($type === 'trending') {
            if ($upcoming) {
                return collect();
            }
            $window = $strategy['window'] ?? 'day';

            return $this->tmdb->getTrendingMoviesAsDto($page, $window)->results;
        }

        if ($type === 'genre' || $type === 'keywords') {
            $minVoteCount = $upcoming ? null : config('tmdb.import.min_vote_count');
            $minVoteAverage = $upcoming ? null : config('tmdb.import.min_vote_average');
            $genreIds = null;
            $keywordIds = null;

            if ($type === 'genre' && isset($strategy['genre_id'])) {
                $genreIds = [(int) $strategy['genre_id']];
            }
            if ($type === 'keywords' && ! empty($strategy['names'])) {
                $keywordIds = $this->tmdb->resolveKeywordIds($strategy['names']);
                if (empty($keywordIds)) {
                    return collect();
                }
            }

            $response = $this->tmdb->discoverMoviesAsDto(
                $page,
                $genreIds,
                $keywordIds,
                $minVoteCount,
                $minVoteAverage,
                $upcoming
            );

            return $response->results;
        }

        return collect();
    }

    /**
     * Fetch discover results for one strategy (genre or keywords).
     *
     * @param  array<string, mixed>  $strategy
     * @return Collection<int, array<string, mixed>>
     */
    protected function fetchStrategyResults(array $strategy, bool $upcoming, int $maxPerStrategy): Collection
    {
        $results = collect();
        $page = 1;
        $minVoteCount = config('tmdb.import.min_vote_count');
        $minVoteAverage = config('tmdb.import.min_vote_average');

        $genreIds = null;
        $keywordIds = null;

        if (($strategy['type'] ?? '') === 'genre' && isset($strategy['genre_id'])) {
            $genreIds = [(int) $strategy['genre_id']];
        }

        if (($strategy['type'] ?? '') === 'keywords' && ! empty($strategy['names'])) {
            $keywordIds = $this->tmdb->resolveKeywordIds($strategy['names']);
            if (empty($keywordIds)) {
                return $results;
            }
        }

        while ($results->count() < $maxPerStrategy) {
            $response = $this->tmdb->discoverMoviesAsDto(
                $page,
                $genreIds,
                $keywordIds,
                $minVoteCount,
                $minVoteAverage,
                $upcoming
            );

            if ($response->isEmpty()) {
                break;
            }

            foreach ($response->results as $movie) {
                $results->push($movie);
                if ($results->count() >= $maxPerStrategy) {
                    break;
                }
            }

            if ($response->results->count() < 20) {
                break;
            }

            $page++;
            usleep(250000);
        }

        return $results;
    }

    /**
     * Legacy single-strategy discovery (War genre only).
     *
     * @return Collection<int, int>
     */
    protected function discoverWithLegacyStrategy(int $limit, bool $upcoming): Collection
    {
        $allResults = collect();
        $page = 1;

        while ($allResults->count() < $limit * 2) {
            $response = $this->tmdb->discoverWarMoviesAsDto($page, $upcoming);
            if ($response->isEmpty()) {
                break;
            }
            foreach ($response->results as $movie) {
                $allResults->push($movie);
            }
            $page++;
            usleep(250000);
        }

        $filtered = $this->filter->filter($allResults, $upcoming);

        return $filtered->take($limit)->values();
    }

    /**
     * Import candidates synchronously using TmdbMovieImportService.
     *
     * @param  Collection<int, int>  $candidateIds
     */
    protected function importSync(Collection $candidateIds, bool $force): int
    {
        $progressBar = $this->output->createProgressBar($candidateIds->count());
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($candidateIds as $tmdbId) {
            if (! $force && Movie::where('tmdb_id', $tmdbId)->exists()) {
                $skipped++;
                $progressBar->advance();

                continue;
            }

            $result = $this->importService->import($tmdbId, $force);
            if ($result !== null) {
                if ($result['action'] === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            }
            $progressBar->advance();
            usleep(250000);
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Import complete!');
        $this->info("New movies created: {$created}");
        $this->info("Existing movies updated: {$updated}");
        $this->info("Movies skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Dispatch ImportTmdbMovieJob for each candidate with delay for rate limiting.
     *
     * @param  Collection<int, int>  $candidateIds
     */
    protected function importAsync(Collection $candidateIds, bool $force): int
    {
        $dispatched = 0;
        $skipped = 0;

        foreach ($candidateIds->values() as $index => $tmdbId) {
            if (! $force && Movie::where('tmdb_id', $tmdbId)->exists()) {
                $skipped++;

                continue;
            }

            ImportTmdbMovieJob::dispatch($tmdbId, $force)
                ->delay(now()->addSeconds((int) (0.25 * $index)));

            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} import jobs.");
        if ($skipped > 0) {
            $this->info("Skipped {$skipped} (already imported).");
        }

        return self::SUCCESS;
    }
}
