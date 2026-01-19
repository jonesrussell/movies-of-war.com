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

    public function handle(): int
    {
        $this->posterService = app(PosterImageService::class);

        $query = Movie::whereNotNull('poster_path');

        // If --all is not set, only optimize posters that don't have optimized versions yet
        if (! $this->option('all')) {
            $this->info('Finding posters that need optimization...');
            $query->where(function ($q) {
                // Check if optimized files exist by looking for any size variant
                // We'll filter in memory since we need to check file existence
            });
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $movies = $limit ? $query->limit($limit)->get() : $query->get();

        if ($movies->isEmpty()) {
            $this->info('No posters found to optimize.');

            return self::SUCCESS;
        }

        $this->info("Found {$movies->count()} posters to optimize.");

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
                $baseFilename = pathinfo($movie->poster_path, PATHINFO_FILENAME);
                $hasOptimized = Storage::disk('public')->exists("posters/{$baseFilename}-342.webp");

                if ($hasOptimized) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }
            }

            try {
                $generated = $this->posterService->optimizePoster($movie->poster_path);

                if (count($generated) > 0) {
                    $optimized++;
                } else {
                    $failed++;
                    $this->newLine();
                    $this->warn("  Failed to optimize poster for: {$movie->title} (ID: {$movie->id})");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Error optimizing poster for: {$movie->title} (ID: {$movie->id}): {$e->getMessage()}");
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

        return self::SUCCESS;
    }
}
