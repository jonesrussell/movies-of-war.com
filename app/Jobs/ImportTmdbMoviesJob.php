<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ImportTmdbMoviesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $limit = 30,
        public bool $downloadPosters = false,
        public bool $random = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $options = [
            '--limit' => $this->limit,
        ];

        if ($this->downloadPosters) {
            $options['--download-posters'] = true;
        }

        if ($this->random) {
            $options['--random'] = true;
        }

        Log::info('Starting TMDB import job', [
            'limit' => $this->limit,
            'download_posters' => $this->downloadPosters,
            'random' => $this->random,
        ]);

        $exitCode = Artisan::call('tmdb:import', $options);
        $output = Artisan::output();

        if ($output) {
            Log::info('TMDB import command output', [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);
        }

        if ($exitCode !== 0) {
            Log::error('TMDB import command failed', [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);
        } else {
            Log::info('TMDB import job completed successfully');
        }
    }
}
