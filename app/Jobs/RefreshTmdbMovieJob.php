<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Movie;
use App\Services\TMDBService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshTmdbMovieJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 60;

    public function __construct(
        public Movie $movie
    ) {
        $this->onQueue('tmdb-refresh');
    }

    public function handle(TMDBService $tmdb): void
    {
        $dto = $tmdb->getMovieDetailsAsDto($this->movie->tmdb_id);

        if ($dto === null) {
            Log::warning('RefreshTmdbMovieJob: TMDB returned no data', [
                'movie_id' => $this->movie->id,
                'tmdb_id' => $this->movie->tmdb_id,
            ]);

            return;
        }

        $attrs = $dto->toMovieAttributes();
        $attrs['tmdb_last_synced_at'] = now();

        $this->movie->fill($attrs);
        $this->movie->save();
    }
}
