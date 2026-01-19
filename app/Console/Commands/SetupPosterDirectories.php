<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetupPosterDirectories extends Command
{
    protected $signature = 'posters:setup-directories';

    protected $description = 'Pre-create all 1,296 possible poster subdirectories (00-99, aa-zz, 0a-9z, etc.)';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $postersPath = 'posters';

        $this->info('Creating poster subdirectories...');

        // Ensure base posters directory exists
        if (! $disk->exists($postersPath)) {
            $disk->makeDirectory($postersPath);
            $this->info("Created base directory: {$postersPath}");
        }

        // Generate all possible 2-character combinations
        $chars = array_merge(range('0', '9'), range('a', 'z'));
        $directories = [];

        // Generate all combinations: 00-99, aa-zz, 0a-9z, a0-z9
        foreach ($chars as $first) {
            foreach ($chars as $second) {
                $directories[] = "{$first}{$second}";
            }
        }

        // Add special directories
        $directories[] = '_misc';
        $directories[] = '_archived';

        $total = count($directories);
        $this->info("Creating {$total} subdirectories...");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $created = 0;
        $skipped = 0;

        foreach ($directories as $subdir) {
            $fullPath = "{$postersPath}/{$subdir}";

            if ($disk->exists($fullPath)) {
                $skipped++;
            } else {
                $disk->makeDirectory($fullPath);
                $created++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Directory setup complete!');
        $this->info("Created: {$created}");
        $this->info("Skipped (already exists): {$skipped}");
        $this->info("Total directories: {$total}");

        return self::SUCCESS;
    }
}
