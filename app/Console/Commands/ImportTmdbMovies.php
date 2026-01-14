<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Tag;
use App\Services\TMDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportTmdbMovies extends Command
{
    protected $signature = 'tmdb:import
                            {--limit=30 : Number of movies to import}
                            {--upcoming : Only upcoming releases}
                            {--force : Re-import existing movies}
                            {--page=1 : Starting page number}
                            {--random : Randomize movie selection}
                            {--debug : Show debug output}';

    protected $description = 'Import war movies from The Movie Database (TMDB)';

    protected TMDBService $tmdb;

    protected bool $debug = false;

    public function handle(): int
    {
        if (! config('tmdb.api_key')) {
            $this->error('TMDB API key not configured. Please set TMDB_API_KEY in your .env file.');
            $this->info('Get your free API key at: https://www.themoviedb.org/settings/api');

            return self::FAILURE;
        }

        $this->tmdb = app(TMDBService::class);
        $limit = (int) $this->option('limit');
        $upcoming = (bool) $this->option('upcoming');
        $force = $this->option('force');
        $startPage = (int) $this->option('page');
        $random = $this->option('random');
        $this->debug = $this->option('debug');

        $pageInfo = $startPage > 1 ? " (starting from page {$startPage})" : '';
        $randomInfo = $random ? ' (randomized)' : '';
        $upcomingInfo = $upcoming ? ' (upcoming only)' : '';
        $debugInfo = $this->debug ? ' [DEBUG MODE]' : '';
        $this->info("Fetching war movies from TMDB (limit: {$limit}){$pageInfo}{$randomInfo}{$upcomingInfo}{$debugInfo}...");

        $warMovieIds = $this->getWarMovieIds($limit, $startPage, $random, $upcoming);

        $this->info("Found {$warMovieIds->count()} movies. Importing...");

        $progressBar = $this->output->createProgressBar($warMovieIds->count());
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($warMovieIds as $tmdbId) {
            try {
                // Skip if movie already exists and --force not set
                if (! $force && Movie::where('tmdb_id', $tmdbId)->exists()) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                $details = $this->tmdb->getMovieDetails($tmdbId);

                if ($details) {
                    $result = $this->importMovie($details);
                    if ($result['action'] === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }
                }

                $progressBar->advance();
                usleep(250000); // Rate limiting: 4 requests per second
            } catch (\Exception $e) {
                $this->warn("\nError importing movie ID {$tmdbId}: {$e->getMessage()}");
            }
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Import complete!');
        $this->info("New movies created: {$created}");
        $this->info("Existing movies updated: {$updated}");
        $this->info("Movies skipped: {$skipped}");

        return self::SUCCESS;
    }

    protected function getWarMovieIds(int $limit, int $startPage, bool $random, bool $upcoming): \Illuminate\Support\Collection
    {
        $ids = collect();

        if ($random) {
            // Get total pages available from TMDB
            $firstPageData = $this->tmdb->discoverWarMovies(1, $upcoming);

            if ($this->debug) {
                $this->line("\n=== DEBUG: First Page Response ===");
                $this->line(json_encode($firstPageData, JSON_PRETTY_PRINT));
                $this->line("===================================\n");
            }

            $totalPages = min($firstPageData['total_pages'], 500); // TMDB limits to 500 pages

            $this->line("Total pages available: {$totalPages}");

            // Generate random page numbers to fetch from
            $pagesToFetch = collect(range(1, $totalPages))
                ->shuffle()
                ->take(ceil($limit / 20)) // Each page has ~20 results
                ->values();

            $this->line('Fetching from random pages: '.implode(', ', $pagesToFetch->take(10)->toArray()).'...');

            foreach ($pagesToFetch as $page) {
                $data = $this->tmdb->discoverWarMovies($page, $upcoming);

                if ($this->debug) {
                    $this->line("\n=== DEBUG: Page {$page} Response ===");
                    $this->line('Results count: '.count($data['results'] ?? []));
                    $this->line('Total pages: '.($data['total_pages'] ?? 'N/A'));
                    $this->line('Total results: '.($data['total_results'] ?? 'N/A'));
                    if (! empty($data['results'])) {
                        $this->line('First result: '.json_encode($data['results'][0] ?? [], JSON_PRETTY_PRINT));
                    }
                    $this->line("===================================\n");
                }

                $movies = $data['results'] ?? [];

                if (empty($movies)) {
                    continue;
                }

                foreach ($movies as $movie) {
                    $ids->push($movie['id']);

                    if ($ids->count() >= $limit) {
                        break 2;
                    }
                }

                usleep(250000); // Rate limiting between page fetches
            }
        } else {
            // Sequential fetch starting from specified page
            $page = $startPage;

            while ($ids->count() < $limit) {
                $data = $this->tmdb->discoverWarMovies($page, $upcoming);

                if ($this->debug) {
                    $this->line("\n=== DEBUG: Page {$page} Response ===");
                    $this->line('Results count: '.count($data['results'] ?? []));
                    $this->line('Total pages: '.($data['total_pages'] ?? 'N/A'));
                    $this->line('Total results: '.($data['total_results'] ?? 'N/A'));
                    if (! empty($data['results'])) {
                        $this->line('First result: '.json_encode($data['results'][0] ?? [], JSON_PRETTY_PRINT));
                    }
                    $this->line("===================================\n");
                }

                $movies = $data['results'] ?? [];

                if (empty($movies)) {
                    break;
                }

                foreach ($movies as $movie) {
                    $ids->push($movie['id']);

                    if ($ids->count() >= $limit) {
                        break;
                    }
                }

                $page++;
            }
        }

        return $ids;
    }

    protected function importMovie(array $details): array
    {
        if ($this->debug) {
            $this->line("\n=== DEBUG: Movie Details Response ===");
            $this->line(json_encode($details, JSON_PRETTY_PRINT));
            $this->line("===================================\n");
        }

        $posterPath = null;
        $posterUrl = null;

        if ($details['poster_path']) {
            $posterPath = $this->tmdb->downloadPoster($details['poster_path']);
            $posterUrl = $this->tmdb->getPosterUrl($details['poster_path']);
        }

        $trailerUrl = $this->tmdb->getYoutubeTrailerUrl($details['videos'] ?? null);

        // First try to find by tmdb_id, then by slug to avoid duplicates
        $slug = Str::slug($details['title']);

        $this->line("\nProcessing: {$details['title']} (TMDB ID: {$details['id']}, Slug: {$slug})");

        $movieByTmdbId = Movie::where('tmdb_id', $details['id'])->first();
        if ($movieByTmdbId) {
            $this->line("  → Found by TMDB ID: {$movieByTmdbId->id}");
            $movie = $movieByTmdbId;
            $action = 'updated';
        } else {
            $movieBySlug = Movie::where('slug', $slug)->first();
            if ($movieBySlug) {
                $this->line("  → Found by slug: {$movieBySlug->id} (existing tmdb_id: {$movieBySlug->tmdb_id})");
                $movie = $movieBySlug;
                $action = 'updated';
            } else {
                $this->line('  → Creating new movie');
                $movie = new Movie(['tmdb_id' => $details['id']]);
                $action = 'created';
            }
        }

        $movie->fill([
            'tmdb_id' => $details['id'],
            'title' => $details['title'],
            'slug' => $slug,
            'release_year' => (int) substr($details['release_date'] ?? now()->year, 0, 4),
            'release_date' => $details['release_date'] ?? null,
            'synopsis' => $details['overview'] ?? '',
            'runtime' => $details['runtime'] ?? null,
            'poster_path' => $posterPath,
            'poster_url' => $posterUrl,
            'trailer_url' => $trailerUrl,
            'imdb_id' => $details['imdb_id'] ?? null,
            'is_upcoming' => ! empty($details['release_date']) && $details['release_date'] > now(),
        ]);

        // Only set status to draft if this is a new movie
        if (! $movie->exists) {
            $movie->status = Movie::STATUS_DRAFT;
        }

        $this->line('  → Saving (exists: '.($movie->exists ? 'yes' : 'no').')');
        $movie->save();
        $this->line("  → Saved as ID: {$movie->id}");

        // Tag the movie
        $this->tagMovie($movie, $details);

        return ['action' => $action];
    }

    protected function tagMovie(Movie $movie, array $details): void
    {
        $tags = collect();

        // Add genre tags
        foreach ($details['genres'] ?? [] as $genre) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($genre['name'])],
                ['name' => $genre['name'], 'type' => 'genre']
            );
            $tags->push($tag->id);
        }

        // Add era tags based on keywords
        if (isset($details['keywords']['keywords'])) {
            foreach ($details['keywords']['keywords'] as $keyword) {
                $keywordName = $keyword['name'];

                if (str_contains($keywordName, 'world war ii') || str_contains($keywordName, 'ww2')) {
                    $tag = Tag::firstOrCreate(['slug' => 'wwii'], ['name' => 'WWII', 'type' => 'era']);
                    $tags->push($tag->id);
                } elseif (str_contains($keywordName, 'world war i') || str_contains($keywordName, 'ww1')) {
                    $tag = Tag::firstOrCreate(['slug' => 'wwi'], ['name' => 'WWI', 'type' => 'era']);
                    $tags->push($tag->id);
                } elseif (str_contains($keywordName, 'vietnam')) {
                    $tag = Tag::firstOrCreate(['slug' => 'vietnam-war'], ['name' => 'Vietnam War', 'type' => 'era']);
                    $tags->push($tag->id);
                }
            }
        }

        $movie->tags()->sync($tags->unique());
    }
}
