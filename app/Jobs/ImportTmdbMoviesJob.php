<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class ImportTmdbMoviesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $limit = 30,
        public bool $downloadPosters = false
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

        Artisan::call('tmdb:import', $options);
    }
}
