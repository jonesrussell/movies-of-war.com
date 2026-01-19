<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanPosters extends Command
{
    protected $signature = 'posters:cleanup-orphans
                            {--dry-run : Show what would be cleaned without actually cleaning}
                            {--archive : Move orphans to posters/_archived/ instead of deleting}
                            {--force : Actually delete files (requires --force flag)}';

    protected $description = 'Find and clean up orphaned poster files not referenced in the database';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $postersPath = 'posters';
        $dryRun = $this->option('dry-run');
        $archive = $this->option('archive');
        $force = $this->option('force');

        // Safety: Never delete without explicit --force flag
        if (! $dryRun && ! $archive && ! $force) {
            $this->error('Safety: Use --dry-run to preview, --archive to move files, or --force to delete.');
            $this->info('Recommended: Use --archive to move orphans to posters/_archived/');

            return self::FAILURE;
        }

        $logFile = storage_path('logs/posters-cleanup-'.date('Y-m-d').'.log');
        $this->logToFile($logFile, 'Starting orphan poster cleanup');

        $this->info('Scanning database for referenced poster paths...');
        $this->logToFile($logFile, 'Scanning database');

        // Get all poster paths from database
        $dbPaths = Movie::whereNotNull('poster_path')
            ->pluck('poster_path')
            ->map(function ($path) {
                // Normalize path (remove leading/trailing slashes, ensure consistent format)
                return ltrim($path, '/');
            })
            ->unique()
            ->toArray();

        $this->info('Found '.count($dbPaths).' poster paths in database.');

        // Get all poster files from filesystem (including subdirectories)
        $this->info('Scanning filesystem for poster files...');
        $this->logToFile($logFile, 'Scanning filesystem');

        $allFiles = $this->getAllPosterFiles($disk, $postersPath);
        $this->info('Found '.count($allFiles).' poster files in filesystem.');

        // Find orphans (files not in database)
        $orphans = [];
        foreach ($allFiles as $file) {
            $normalizedFile = ltrim($file, '/');
            // Check if file is referenced (exact match or as base for optimized versions)
            $isReferenced = $this->isFileReferenced($normalizedFile, $dbPaths);

            if (! $isReferenced) {
                $orphans[] = $file;
            }
        }

        if (empty($orphans)) {
            $this->info('No orphaned files found. All poster files are referenced in the database.');
            $this->logToFile($logFile, 'No orphans found');

            return self::SUCCESS;
        }

        $this->warn('Found '.count($orphans).' orphaned files.');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No files will be modified');
            $this->newLine();
            foreach ($orphans as $orphan) {
                $this->line("  Orphan: {$orphan}");
            }
            $this->logToFile($logFile, 'Dry run: Found '.count($orphans).' orphans', $orphans);

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar(count($orphans));
        $progressBar->start();

        $archived = 0;
        $deleted = 0;
        $failed = 0;

        // Ensure archive directory exists if using archive mode
        if ($archive) {
            $disk->makeDirectory('posters/_archived');
        }

        foreach ($orphans as $orphan) {
            try {
                if ($archive) {
                    // Move to archive
                    $archivedPath = 'posters/_archived/'.basename($orphan);
                    $disk->move($orphan, $archivedPath);
                    $archived++;
                    $this->logToFile($logFile, "Archived: {$orphan} -> {$archivedPath}");
                } elseif ($force) {
                    // Delete file
                    $disk->delete($orphan);
                    $deleted++;
                    $this->logToFile($logFile, "Deleted: {$orphan}");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("  Error processing {$orphan}: {$e->getMessage()}");
                $this->logToFile($logFile, "Error: {$orphan} - {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $summary = [];
        if ($archived > 0) {
            $summary[] = "Archived: {$archived}";
        }
        if ($deleted > 0) {
            $summary[] = "Deleted: {$deleted}";
        }
        if ($failed > 0) {
            $summary[] = "Failed: {$failed}";
        }

        $this->info('Cleanup complete!');
        foreach ($summary as $line) {
            $this->info("  {$line}");
        }

        $this->logToFile($logFile, 'Cleanup complete: '.implode(', ', $summary));
        $this->info("Log file: {$logFile}");

        return self::SUCCESS;
    }

    /**
     * Get all poster files recursively from filesystem.
     */
    protected function getAllPosterFiles($disk, string $path): array
    {
        $files = [];
        $directories = $disk->directories($path);

        // Get files in current directory
        foreach ($disk->files($path) as $file) {
            $files[] = $file;
        }

        // Recursively get files from subdirectories
        foreach ($directories as $directory) {
            $files = array_merge($files, $this->getAllPosterFiles($disk, $directory));
        }

        return $files;
    }

    /**
     * Check if a file is referenced in the database.
     * Handles both exact matches and optimized variants (e.g., filename-342.webp).
     */
    protected function isFileReferenced(string $file, array $dbPaths): bool
    {
        $filename = basename($file);
        $baseFilename = pathinfo($filename, PATHINFO_FILENAME);

        // Remove size suffix if present (e.g., "filename-342" -> "filename")
        $baseFilename = preg_replace('/-\d+$/', '', $baseFilename);

        foreach ($dbPaths as $dbPath) {
            $dbFilename = basename($dbPath);
            $dbBaseFilename = pathinfo($dbFilename, PATHINFO_FILENAME);

            // Check if this file matches the database path or is an optimized variant
            if ($baseFilename === $dbBaseFilename) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log message to both console and file.
     */
    protected function logToFile(string $logFile, string $message, ?array $data = null): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}";

        if ($data !== null) {
            $logMessage .= "\n".json_encode($data, JSON_PRETTY_PRINT);
        }

        file_put_contents($logFile, $logMessage."\n", FILE_APPEND);
    }
}
