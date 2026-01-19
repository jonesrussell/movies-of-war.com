<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\PosterImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizePosters extends Command
{
    protected $signature = 'posters:optimize
                            {--all : Optimize all posters, including those already optimized}
                            {--limit= : Limit the number of posters to optimize}';

    protected $description = 'Generate optimized sizes and formats for existing poster images';

    protected PosterImageService $posterService;

    protected ?string $logFile = null;

    public function handle(): int
    {
        $this->posterService = app(PosterImageService::class);
        $verbose = $this->getOutput()->isVerbose();

        // Setup logging
        $this->logFile = storage_path('logs/posters-optimize-'.date('Y-m-d').'.log');
        $this->log('Starting poster optimization', $verbose);

        $query = Movie::whereNotNull('poster_path');

        // If --all is not set, only optimize posters that don't have optimized versions yet
        if (! $this->option('all')) {
            $this->info('Finding posters that need optimization...');
            $this->log('Finding posters that need optimization', $verbose);
            $query->where(function ($q) {
                // Check if optimized files exist by looking for any size variant
                // We'll filter in memory since we need to check file existence
            });
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $movies = $limit ? $query->limit($limit)->get() : $query->get();

        if ($movies->isEmpty()) {
            $this->info('No posters found to optimize.');
            $this->log('No posters found to optimize', $verbose);

            return self::SUCCESS;
        }

        $this->info("Found {$movies->count()} posters to optimize.");
        $this->log("Found {$movies->count()} posters to optimize", $verbose);

        $progressBar = $this->output->createProgressBar($movies->count());
        $progressBar->start();

        $optimized = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($movies as $movie) {
            if (! $movie->poster_path) {
                $skipped++;
                $progressBar->advance();

                continue;
            }

            // Check if already optimized (if --all not set)
            if (! $this->option('all')) {
                $pathInfo = pathinfo($movie->poster_path);
                $baseFilename = $pathInfo['filename'];
                $directory = $pathInfo['dirname'];
                // Check for optimized version in the same directory
                $hasOptimized = Storage::disk('public')->exists("{$directory}/{$baseFilename}-342.webp");

                if ($hasOptimized) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }
            }

            try {
                // Check if file exists before trying to optimize
                if (! Storage::disk('public')->exists($movie->poster_path)) {
                    $failed++;
                    $this->newLine();
                    $this->warn("  Poster file not found: {$movie->title} (ID: {$movie->id}) - {$movie->poster_path}");
                    $this->log("Poster file not found: {$movie->title} (ID: {$movie->id}) - {$movie->poster_path}", $verbose);
                    $progressBar->advance();

                    continue;
                }

                $generated = $this->posterService->optimizePoster($movie->poster_path);

                if (count($generated) > 0) {
                    $optimized++;
                    $this->log("Optimized: {$movie->title} (ID: {$movie->id}) - Generated ".count($generated).' files', $verbose);
                } else {
                    $failed++;
                    $this->newLine();
                    $this->warn("  Failed to optimize poster for: {$movie->title} (ID: {$movie->id}) - No files generated");
                    $this->log("Failed to optimize: {$movie->title} (ID: {$movie->id}) - No files generated. Path: {$movie->poster_path}", $verbose);
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Error optimizing poster for: {$movie->title} (ID: {$movie->id}): {$e->getMessage()}");
                $this->log("Error optimizing {$movie->title} (ID: {$movie->id}): {$e->getMessage()}. Path: {$movie->poster_path}", $verbose);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Optimization complete!');
        $this->info("Optimized: {$optimized}");
        $this->info("Skipped: {$skipped}");
        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }

        $this->log("Optimization complete. Optimized: {$optimized}, Skipped: {$skipped}, Failed: {$failed}", $verbose);

        if ($this->logFile) {
            $this->info("Log file: {$this->logFile}");
        }

        return self::SUCCESS;
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
