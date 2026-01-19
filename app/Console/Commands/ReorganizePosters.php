<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReorganizePosters extends Command
{
    protected $signature = 'posters:reorganize
                            {--dry-run : Show what would be moved without actually moving files}';

    protected $description = 'Reorganize poster images into subdirectories (posters/XX/filename.ext)';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $postersPath = 'posters';
        $dryRun = $this->option('dry-run');

        if (! $disk->exists($postersPath)) {
            $this->info('Posters directory does not exist.');

            return self::SUCCESS;
        }

        $this->info('Scanning poster files...');
        $files = $disk->files($postersPath);

        // Filter out files that are already in subdirectories
        $filesToMove = collect($files)->filter(function ($file) {
            // Skip files already in subdirectories (posters/XX/filename.ext)
            $relativePath = str_replace('posters/', '', $file);
            $parts = explode('/', $relativePath);

            return count($parts) === 1; // Only files directly in posters/
        });

        if ($filesToMove->isEmpty()) {
            $this->info('No files need to be reorganized. All posters are already organized.');

            return self::SUCCESS;
        }

        $this->info("Found {$filesToMove->count()} files to reorganize.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be moved');
        }

        $progressBar = $this->output->createProgressBar($filesToMove->count());
        $progressBar->start();

        $moved = 0;
        $failed = 0;

        foreach ($filesToMove as $file) {
            $filename = basename($file);
            $subdir = substr($filename, 0, 2);

            if (strlen($subdir) < 2) {
                // Skip files with names shorter than 2 characters
                $failed++;
                $progressBar->advance();

                continue;
            }

            $newPath = "posters/{$subdir}/{$filename}";

            try {
                if ($dryRun) {
                    $this->newLine();
                    $this->line("  Would move: {$file} -> {$newPath}");
                } else {
                    // Ensure subdirectory exists
                    $disk->makeDirectory("posters/{$subdir}");

                    // Move the file
                    if ($disk->exists($file)) {
                        $disk->move($file, $newPath);
                        $moved++;
                    } else {
                        $failed++;
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Error moving {$file}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('Dry run complete. Use without --dry-run to actually move files.');
        } else {
            $this->info('Reorganization complete!');
            $this->info("Moved: {$moved}");
            if ($failed > 0) {
                $this->warn("Failed: {$failed}");
            }

            // Update movie records with new paths
            $this->info('Updating movie records...');
            $this->updateMoviePaths();
        }

        return self::SUCCESS;
    }

    protected function updateMoviePaths(): void
    {
        $disk = Storage::disk('public');
        $movies = Movie::whereNotNull('poster_path')->get();
        $updated = 0;

        foreach ($movies as $movie) {
            if (! $movie->poster_path) {
                continue;
            }

            // Check if path is already in subdirectory format (posters/XX/filename.ext)
            $pathParts = explode('/', $movie->poster_path);
            if (count($pathParts) === 3 && $pathParts[0] === 'posters' && strlen($pathParts[1]) === 2) {
                // Already in subdirectory format, skip
                continue;
            }

            $filename = basename($movie->poster_path);
            $subdir = substr($filename, 0, 2);

            if (strlen($subdir) < 2) {
                continue;
            }

            $newPath = "posters/{$subdir}/{$filename}";

            // Check if new path exists
            if ($disk->exists($newPath)) {
                $movie->poster_path = $newPath;
                $movie->save();
                $updated++;
            }
        }

        $this->info("Updated {$updated} movie records.");
    }
}
