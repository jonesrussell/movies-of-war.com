<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\TMDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegeneratePosters extends Command
{
    protected $signature = 'posters:regenerate
                            {--limit= : Limit the number of movies to process}';

    protected $description = 'Delete all poster files and regenerate them from TMDB';

    protected TMDBService $tmdbService;

    protected ?string $logFile = null;

    public function handle(): int
    {
        $this->tmdbService = app(TMDBService::class);
        $verbose = $this->getOutput()->isVerbose();

        // Setup logging
        $this->logFile = storage_path('logs/posters-regenerate-'.date('Y-m-d').'.log');
        $this->log('Starting poster regeneration', $verbose);

        // Step 1: Delete all poster files
        $this->info('Deleting all existing poster files...');
        $this->log('Deleting all existing poster files', $verbose);

        $disk = Storage::disk('public');
        if ($disk->exists('posters')) {
            $disk->deleteDirectory('posters');
            $this->info('All poster files deleted.');
            $this->log('All poster files deleted', $verbose);
        } else {
            $this->info('No poster directory found.');
            $this->log('No poster directory found', $verbose);
        }

        // Step 2: Find movies to process
        $this->info('Finding movies to regenerate...');
        $this->log('Finding movies to regenerate', $verbose);

        $query = Movie::where(function ($q) {
            $q->whereNotNull('tmdb_id')
                ->orWhereNotNull('poster_url');
        });

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $movies = $limit ? $query->limit($limit)->get() : $query->get();

        if ($movies->isEmpty()) {
            $this->info('No movies found to regenerate.');
            $this->log('No movies found to regenerate', $verbose);

            return self::SUCCESS;
        }

        $this->info("Found {$movies->count()} movies to regenerate.");
        $this->log("Found {$movies->count()} movies to regenerate", $verbose);

        // Step 3: Regenerate posters
        $progressBar = $this->output->createProgressBar($movies->count());
        $progressBar->start();

        $regenerated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($movies as $movie) {
            $posterPath = null;
            $posterUrl = null;

            try {
                // Get poster path from TMDB
                if ($movie->tmdb_id) {
                    // Fetch fresh data from TMDB API
                    $this->log("Fetching TMDB data for: {$movie->title} (ID: {$movie->id}, TMDB ID: {$movie->tmdb_id})", $verbose);
                    $movieData = $this->tmdbService->getMovieDetailsAsDto($movie->tmdb_id);

                    if ($movieData && $movieData->posterPath) {
                        $posterPath = $movieData->posterPath;
                        $posterUrl = $this->tmdbService->getPosterUrl($posterPath);
                    } else {
                        $skipped++;
                        $this->log("No poster path in TMDB data for: {$movie->title} (ID: {$movie->id})", $verbose);
                        $progressBar->advance();

                        continue;
                    }

                    // Rate limiting: 4 req/sec = 250ms between requests
                    usleep(250000);
                } elseif ($movie->poster_url) {
                    // Extract TMDB poster path from URL
                    $extractedPath = $this->extractPosterPathFromUrl($movie->poster_url);

                    if ($extractedPath) {
                        $posterPath = $extractedPath;
                        $posterUrl = $this->tmdbService->getPosterUrl($posterPath);
                    } else {
                        $skipped++;
                        $this->log("Could not extract poster path from URL for: {$movie->title} (ID: {$movie->id}) - {$movie->poster_url}", $verbose);
                        $progressBar->advance();

                        continue;
                    }
                } else {
                    $skipped++;
                    $this->log("No TMDB ID or poster URL for: {$movie->title} (ID: {$movie->id})", $verbose);
                    $progressBar->advance();

                    continue;
                }

                // Download and regenerate poster
                $this->log("Downloading poster for: {$movie->title} (ID: {$movie->id})", $verbose);
                $downloadedPath = $this->tmdbService->downloadPoster($posterPath);

                if ($downloadedPath) {
                    // Update movie with new poster path and URL
                    $movie->poster_path = $downloadedPath;
                    $movie->poster_url = $posterUrl;
                    $movie->save();

                    $regenerated++;
                    $this->log("Regenerated poster for: {$movie->title} (ID: {$movie->id}) - {$downloadedPath}", $verbose);
                } else {
                    $failed++;
                    $this->newLine();
                    $this->warn("  Failed to download poster for: {$movie->title} (ID: {$movie->id})");
                    $this->log("Failed to download poster for: {$movie->title} (ID: {$movie->id})", $verbose);
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Error regenerating poster for: {$movie->title} (ID: {$movie->id}): {$e->getMessage()}");
                $this->log("Error regenerating poster for: {$movie->title} (ID: {$movie->id}): {$e->getMessage()}", $verbose);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Regeneration complete!');
        $this->info("Regenerated: {$regenerated}");
        $this->info("Skipped: {$skipped}");
        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }

        $this->log("Regeneration complete. Regenerated: {$regenerated}, Skipped: {$skipped}, Failed: {$failed}", $verbose);

        if ($this->logFile) {
            $this->info("Log file: {$this->logFile}");
        }

        return self::SUCCESS;
    }

    /**
     * Extract TMDB poster path from URL.
     */
    protected function extractPosterPathFromUrl(?string $posterUrl): ?string
    {
        if (! $posterUrl || ! str_contains($posterUrl, 'image.tmdb.org')) {
            return null;
        }

        // Remove .webp extension if present
        $cleanUrl = preg_replace('/\.webp$/', '', $posterUrl);

        // Extract path: /t/p/w500/abc123.jpg -> /abc123.jpg
        if (preg_match('/\/t\/p\/w\d+\/(.+)$/', $cleanUrl, $matches)) {
            return '/'.$matches[1];
        }

        return null;
    }

    /**
     * Log message to file with timestamp.
     */
    protected function log(string $message, bool $verbose = false): void
    {
        if (! $this->logFile) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}";

        file_put_contents($this->logFile, $logMessage."\n", FILE_APPEND);

        if ($verbose) {
            $this->line("  [LOG] {$message}");
        }
    }
}
