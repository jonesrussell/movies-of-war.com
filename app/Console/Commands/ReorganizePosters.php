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

    protected ?string $logFile = null;

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $postersPath = 'posters';
        $dryRun = $this->option('dry-run');
        $verbose = $this->getOutput()->isVerbose();

        // Setup logging
        $this->logFile = storage_path('logs/posters-reorganize-'.date('Y-m-d').'.log');
        $this->log('Starting reorganization', $verbose);

        if (! $disk->exists($postersPath)) {
            $this->info('Posters directory does not exist.');
            $this->log('Posters directory does not exist', $verbose);

            return self::SUCCESS;
        }

        $this->info('Scanning poster files...');
        $this->log('Scanning poster files', $verbose);
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
            $this->log('No files need reorganization', $verbose);

            return self::SUCCESS;
        }

        $this->info("Found {$filesToMove->count()} files to reorganize.");
        $this->log("Found {$filesToMove->count()} files to reorganize", $verbose);

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be moved');
            $this->log('DRY RUN MODE - No files will be moved', $verbose);
        }

        $progressBar = $this->output->createProgressBar($filesToMove->count());
        $progressBar->start();

        $moved = 0;
        $failed = 0;

        foreach ($filesToMove as $file) {
            $filename = basename($file);
            // Normalize to lowercase and handle short filenames
            $subdir = strtolower(substr($filename, 0, 2)) ?: '_misc';

            $newPath = "posters/{$subdir}/{$filename}";

            try {
                if ($dryRun) {
                    $this->newLine();
                    $this->line("  Would move: {$file} -> {$newPath}");
                    $this->log("Would move: {$file} -> {$newPath}", $verbose);
                } else {
                    // Ensure subdirectory exists
                    $disk->makeDirectory("posters/{$subdir}");

                    // Move the file
                    if ($disk->exists($file)) {
                        $disk->move($file, $newPath);
                        $moved++;
                        $this->log("Moved: {$file} -> {$newPath}", $verbose);
                    } else {
                        $failed++;
                        $this->log("File not found: {$file}", $verbose);
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Error moving {$file}: {$e->getMessage()}");
                $this->log("Error moving {$file}: {$e->getMessage()}", $verbose);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('Dry run complete. Use without --dry-run to actually move files.');
            $this->log("Dry run complete. Would move: {$filesToMove->count()}", $verbose);
        } else {
            $this->info('Reorganization complete!');
            $this->info("Moved: {$moved}");
            if ($failed > 0) {
                $this->warn("Failed: {$failed}");
            }

            $this->log("Reorganization complete. Moved: {$moved}, Failed: {$failed}", $verbose);

            // Update movie records with new paths
            $this->info('Updating movie records...');
            $this->log('Updating movie records', $verbose);
            $this->updateMoviePaths();
        }

        if ($this->logFile) {
            $this->info("Log file: {$this->logFile}");
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
            // Normalize to lowercase and handle short filenames
            $subdir = strtolower(substr($filename, 0, 2)) ?: '_misc';

            $newPath = "posters/{$subdir}/{$filename}";

            // Check if new path exists
            if ($disk->exists($newPath)) {
                $movie->poster_path = $newPath;
                $movie->save();
                $updated++;
            }
        }

        $this->info("Updated {$updated} movie records.");
        $this->log("Updated {$updated} movie records", $this->option('verbose'));
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
