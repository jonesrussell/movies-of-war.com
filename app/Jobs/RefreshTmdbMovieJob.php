<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Movie;
use App\Services\PersonSyncService;
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

    public function handle(TMDBService $tmdb, PersonSyncService $personSync): void
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
        unset($attrs['slug'], $attrs['poster_path'], $attrs['poster_url'], $attrs['cast'], $attrs['crew']);
        $attrs['tmdb_last_synced_at'] = now();

        // Don't overwrite existing release_year/release_date with null
        // (TMDB may lack dates for films still in production)
        if ($attrs['release_year'] === null) {
            unset($attrs['release_year'], $attrs['release_date']);
        }

        $this->movie->fill($attrs);
        $this->movie->save();

        $peopleData = $personSync->syncFromMovieDto($this->movie, $dto);
        $this->movie->update([
            'cast' => $peopleData['cast'],
            'crew' => $peopleData['crew'],
        ]);
    }
}
