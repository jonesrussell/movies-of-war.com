<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Tmdb\TmdbMovieData;
use App\Enums\MovieStatus;
use App\Enums\TagType;
use App\Models\Movie;
use App\Models\Tag;
use Illuminate\Support\Str;

class TmdbMovieImportService
{
    public function __construct(
        protected TMDBService $tmdb,
        protected PersonSyncService $personSync
    ) {}

    /**
     * Import one movie by TMDB ID. Creates or updates the movie, syncs people and tags.
     *
     * @return array{movie: Movie, action: 'created'|'updated'}|null Null when TMDB returns 404 or no data.
     */
    public function import(int $tmdbId, bool $force = false): ?array
    {
        $dto = $this->tmdb->getMovieDetailsAsDto($tmdbId);

        if ($dto === null) {
            return null;
        }

        $posterPath = null;
        $posterUrl = null;
        if ($dto->posterPath) {
            $posterPath = $this->tmdb->downloadPoster($dto->posterPath);
            $posterUrl = $this->tmdb->getPosterUrl($dto->posterPath);
        }

        $slug = $this->resolveUniqueSlug($dto);

        $movieByTmdbId = Movie::where('tmdb_id', $dto->id)->first();
        if ($movieByTmdbId) {
            $movie = $movieByTmdbId;
            $action = 'updated';
        } else {
            $movieBySlug = Movie::where('slug', $slug)->first();
            if ($movieBySlug) {
                $movie = $movieBySlug;
                $action = 'updated';
            } else {
                $movie = new Movie(['tmdb_id' => $dto->id]);
                $action = 'created';
            }
        }

        $movie->fill(array_merge($dto->toMovieAttributes(), [
            'slug' => $slug,
            'poster_path' => $posterPath,
            'poster_url' => $posterUrl,
            'trailer_url' => $dto->getTrailerUrl(),
            'tmdb_last_synced_at' => now(),
        ]));

        if (! $movie->exists) {
            $movie->status = MovieStatus::Draft;
        }

        $movie->save();

        $peopleData = $this->personSync->syncFromMovieDto($movie, $dto);
        $movie->update([
            'cast' => $peopleData['cast'],
            'crew' => $peopleData['crew'],
        ]);

        $this->syncTags($movie, $dto);

        return [
            'movie' => $movie,
            'action' => $action,
        ];
    }

    /**
     * Generate a unique slug for a movie being imported from TMDB.
     */
    public function resolveUniqueSlug(TmdbMovieData $dto): string
    {
        $baseSlug = Str::slug($dto->title);
        $releaseYear = $dto->getReleaseYear();

        if (! Movie::where('slug', $baseSlug)->exists()) {
            return $baseSlug;
        }

        $slugWithYear = $releaseYear ? "{$baseSlug}-{$releaseYear}" : "{$baseSlug}-{$dto->id}";
        if (! Movie::where('slug', $slugWithYear)->exists()) {
            return $slugWithYear;
        }

        return "{$baseSlug}-{$dto->id}";
    }

    protected function syncTags(Movie $movie, TmdbMovieData $dto): void
    {
        $tagIds = collect();

        foreach ($dto->genres as $genre) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($genre->name)],
                ['name' => $genre->name, 'type' => TagType::Genre]
            );
            $tagIds->push($tag->id);
        }

        foreach ($dto->keywords as $keyword) {
            $matchedEra = $keyword->matchEra();
            if ($matchedEra !== null) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($matchedEra)],
                    ['name' => $matchedEra, 'type' => TagType::Era]
                );
                $tagIds->push($tag->id);
            }
        }

        $movie->tags()->sync($tagIds->unique()->values()->all());
    }
}
