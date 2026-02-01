<?php

declare(strict_types=1);

namespace App\Data\Tmdb;

/**
 * Data Transfer Object for TMDB Person details.
 */
readonly class TmdbPersonData
{
    /**
     * @param  array<int, array{tmdb_movie_id: int, title: string|null, character: string|null, release_date: string|null, order: int}>  $filmographyCast
     * @param  array<int, array{tmdb_movie_id: int, title: string|null, job: string, department: string|null, release_date: string|null}>  $filmographyCrew
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $biography,
        public ?string $birthday,
        public ?string $deathday,
        public ?string $placeOfBirth,
        public ?array $alsoKnownAs,
        public ?string $profilePath,
        public array $filmographyCast = [],
        public array $filmographyCrew = [],
    ) {}

    /**
     * Create from TMDB API response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        $castRaw = $data['movie_credits']['cast'] ?? [];
        $crewRaw = $data['movie_credits']['crew'] ?? [];
        $cast = is_array($castRaw) ? self::parseFilmographyCast($castRaw) : [];
        $crew = is_array($crewRaw) ? self::parseFilmographyCrew($crewRaw) : [];

        $alsoKnownAs = $data['also_known_as'] ?? null;
        if (is_array($alsoKnownAs)) {
            $alsoKnownAs = array_values(array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : null, $alsoKnownAs)));
        } else {
            $alsoKnownAs = [];
        }

        return new self(
            id: (int) ($data['id'] ?? 0),
            name: trim((string) ($data['name'] ?? '')),
            biography: isset($data['biography']) && $data['biography'] !== '' ? (string) $data['biography'] : null,
            birthday: isset($data['birthday']) && $data['birthday'] !== '' ? (string) $data['birthday'] : null,
            deathday: isset($data['deathday']) && $data['deathday'] !== '' ? (string) $data['deathday'] : null,
            placeOfBirth: isset($data['place_of_birth']) && $data['place_of_birth'] !== '' ? (string) $data['place_of_birth'] : null,
            alsoKnownAs: $alsoKnownAs !== [] ? $alsoKnownAs : null,
            profilePath: isset($data['profile_path']) && $data['profile_path'] !== '' ? (string) $data['profile_path'] : null,
            filmographyCast: $cast,
            filmographyCrew: $crew,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $cast
     * @return array<int, array{tmdb_movie_id: int, title: string|null, character: string|null, release_date: string|null, order: int}>
     */
    private static function parseFilmographyCast(array $cast): array
    {
        $parsed = [];
        foreach ($cast as $item) {
            $movieId = isset($item['id']) ? (int) $item['id'] : null;
            if ($movieId === null) {
                continue;
            }
            $parsed[] = [
                'tmdb_movie_id' => $movieId,
                'title' => isset($item['title']) ? (string) $item['title'] : null,
                'character' => isset($item['character']) && $item['character'] !== '' ? (string) $item['character'] : null,
                'release_date' => isset($item['release_date']) && $item['release_date'] !== '' ? (string) $item['release_date'] : null,
                'order' => isset($item['order']) ? (int) $item['order'] : 999,
            ];
        }
        usort($parsed, fn (array $a, array $b) => ($b['release_date'] ?? '') <=> ($a['release_date'] ?? ''));

        return array_values($parsed);
    }

    /**
     * @param  array<int, array<string, mixed>>  $crew
     * @return array<int, array{tmdb_movie_id: int, title: string|null, job: string, department: string|null, release_date: string|null}>
     */
    private static function parseFilmographyCrew(array $crew): array
    {
        $seen = [];
        $parsed = [];
        foreach ($crew as $item) {
            $movieId = isset($item['id']) ? (int) $item['id'] : null;
            $job = isset($item['job']) ? trim((string) $item['job']) : '';
            if ($movieId === null || $job === '') {
                continue;
            }
            $key = $movieId.'|'.$job;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $parsed[] = [
                'tmdb_movie_id' => $movieId,
                'title' => isset($item['title']) ? (string) $item['title'] : null,
                'job' => $job,
                'department' => isset($item['department']) && $item['department'] !== '' ? (string) $item['department'] : null,
                'release_date' => isset($item['release_date']) && $item['release_date'] !== '' ? (string) $item['release_date'] : null,
            ];
        }
        usort($parsed, fn (array $a, array $b) => ($b['release_date'] ?? '') <=> ($a['release_date'] ?? ''));

        return array_values($parsed);
    }

    /**
     * Convert to array suitable for Person model creation/update.
     *
     * @return array<string, mixed>
     */
    public function toPersonAttributes(): array
    {
        return [
            'tmdb_id' => $this->id,
            'name' => $this->name,
            'biography' => $this->biography,
            'birthday' => $this->birthday,
            'deathday' => $this->deathday,
            'place_of_birth' => $this->placeOfBirth,
            'also_known_as' => $this->alsoKnownAs,
            'profile_path' => $this->profilePath,
        ];
    }

    /**
     * Build filmography entries for pivot sync. Returns list of [movie_id, pivot] for attach.
     * Only includes movies that exist in our DB. Deduped by (movie_id, job).
     *
     * @param  array<int, int>  $tmdbIdToMovieId  Map of TMDB movie ID => our Movie ID
     * @return array<int, array{0: int, 1: array{job: string, character: string|null, department: string|null, cast_order: int|null}}>
     */
    public function buildFilmographyEntries(array $tmdbIdToMovieId): array
    {
        $entries = [];
        $seen = [];
        foreach ($this->filmographyCast as $item) {
            $movieId = $tmdbIdToMovieId[$item['tmdb_movie_id']] ?? null;
            if ($movieId === null) {
                continue;
            }
            $key = $movieId.'|Actor|'.($item['character'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $entries[] = [
                $movieId,
                [
                    'job' => 'Actor',
                    'character' => $item['character'],
                    'department' => 'Acting',
                    'cast_order' => $item['order'],
                ],
            ];
        }
        foreach ($this->filmographyCrew as $item) {
            $movieId = $tmdbIdToMovieId[$item['tmdb_movie_id']] ?? null;
            if ($movieId === null) {
                continue;
            }
            $key = $movieId.'|'.$item['job'].'|';
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $entries[] = [
                $movieId,
                [
                    'job' => $item['job'],
                    'character' => null,
                    'department' => $item['department'],
                    'cast_order' => null,
                ],
            ];
        }

        return $entries;
    }
}
