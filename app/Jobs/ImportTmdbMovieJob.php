<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\TmdbMovieImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportTmdbMovieJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public function __construct(
        public int $tmdbId,
        public bool $force = false
    ) {
        $this->onQueue('tmdb-import');
    }

    public function handle(TmdbMovieImportService $importService): void
    {
        $result = $importService->import($this->tmdbId, $this->force);

        if ($result === null) {
            Log::warning('ImportTmdbMovieJob: TMDB returned no data (404 or error), skipping', [
                'tmdb_id' => $this->tmdbId,
            ]);

            return;
        }

        Log::info('ImportTmdbMovieJob: movie imported', [
            'tmdb_id' => $this->tmdbId,
            'movie_id' => $result['movie']->id,
            'action' => $result['action'],
        ]);
    }
}
