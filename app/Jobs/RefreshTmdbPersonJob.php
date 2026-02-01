<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Movie;
use App\Models\Person;
use App\Services\TMDBService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshTmdbPersonJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 60;

    public function __construct(
        public Person $person
    ) {
        $this->onQueue('tmdb-refresh');
    }

    public function handle(TMDBService $tmdb): void
    {
        $dto = $tmdb->getPersonDetailsAsDto($this->person->tmdb_id);

        if ($dto === null) {
            Log::warning('RefreshTmdbPersonJob: TMDB returned no data', [
                'person_id' => $this->person->id,
                'tmdb_id' => $this->person->tmdb_id,
            ]);

            return;
        }

        $attrs = $dto->toPersonAttributes();
        $attrs['name'] = $dto->name;
        $attrs['tmdb_last_synced_at'] = now();

        $this->person->update($attrs);

        $tmdbMovieIds = array_unique(array_merge(
            array_column($dto->filmographyCast, 'tmdb_movie_id'),
            array_column($dto->filmographyCrew, 'tmdb_movie_id')
        ));

        $tmdbIdToMovieId = Movie::whereIn('tmdb_id', $tmdbMovieIds)->pluck('id', 'tmdb_id')->all();

        $entries = $dto->buildFilmographyEntries($tmdbIdToMovieId);

        $this->person->movies()->detach();

        foreach ($entries as [$movieId, $pivot]) {
            $this->person->movies()->attach($movieId, $pivot);
        }
    }
}
