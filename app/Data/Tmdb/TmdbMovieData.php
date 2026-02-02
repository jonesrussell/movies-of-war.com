<?php

declare(strict_types=1);

namespace App\Data\Tmdb;

use Illuminate\Support\Collection;

/**
 * Data Transfer Object for TMDB Movie details.
 */
readonly class TmdbMovieData
{
    /**
     * @param  Collection<int, TmdbGenreData>  $genres
     * @param  Collection<int, TmdbVideoData>  $videos
     * @param  Collection<int, TmdbKeywordData>  $keywords
     * @param  array<int, string>  $writers
     * @param  array<int, array{tmdb_id: int, name: string, character: string|null, order: int, profile_path: string|null}>  $cast
     * @param  array<int, array{tmdb_id: int, name: string, job: string, department: string|null, profile_path: string|null}>  $crew
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $originalTitle,
        public ?string $overview,
        public ?string $releaseDate,
        public ?int $runtime,
        public ?string $posterPath,
        public ?string $backdropPath,
        public ?string $imdbId,
        public ?float $voteAverage,
        public ?int $voteCount,
        public ?float $popularity,
        public Collection $genres,
        public Collection $videos,
        public Collection $keywords,
        public ?string $director = null,
        public array $writers = [],
        public ?string $productionStatus = null,
        public ?string $originalLanguage = null,
        public ?int $budget = null,
        public ?int $revenue = null,
        public array $cast = [],
        public array $crew = [],
    ) {}

    /**
     * Create from TMDB API response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        $credits = isset($data['credits']['crew'])
            ? self::parseCredits($data['credits']['crew'])
            : ['director' => null, 'writers' => []];

        $castRaw = $data['credits']['cast'] ?? [];
        $crewRaw = $data['credits']['crew'] ?? [];
        $cast = is_array($castRaw) ? self::parseCast($castRaw) : [];
        $crew = is_array($crewRaw) ? self::parseCrew($crewRaw) : [];

        return new self(
            id: $data['id'],
            title: $data['title'] ?? '',
            originalTitle: $data['original_title'] ?? null,
            overview: $data['overview'] ?? null,
            releaseDate: self::normalizeReleaseDate(
                isset($data['release_date']) && $data['release_date'] !== '' ? (string) $data['release_date'] : null
            ),
            runtime: isset($data['runtime']) ? (int) $data['runtime'] : null,
            posterPath: $data['poster_path'] ?? null,
            backdropPath: $data['backdrop_path'] ?? null,
            imdbId: $data['imdb_id'] ?? null,
            voteAverage: isset($data['vote_average']) ? (float) $data['vote_average'] : null,
            voteCount: isset($data['vote_count']) ? (int) $data['vote_count'] : null,
            popularity: isset($data['popularity']) ? (float) $data['popularity'] : null,
            genres: collect($data['genres'] ?? [])
                ->map(fn (array $genre) => TmdbGenreData::fromApiResponse($genre)),
            videos: collect($data['videos']['results'] ?? [])
                ->map(fn (array $video) => TmdbVideoData::fromApiResponse($video)),
            keywords: collect($data['keywords']['keywords'] ?? [])
                ->map(fn (array $keyword) => TmdbKeywordData::fromApiResponse($keyword)),
            director: $credits['director'],
            writers: $credits['writers'],
            productionStatus: isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : null,
            originalLanguage: isset($data['original_language']) && $data['original_language'] !== '' ? (string) $data['original_language'] : null,
            budget: isset($data['budget']) && $data['budget'] > 0 ? (int) $data['budget'] : null,
            revenue: isset($data['revenue']) && $data['revenue'] > 0 ? (int) $data['revenue'] : null,
            cast: $cast,
            crew: $crew,
        );
    }

    /**
     * Normalize TMDB release_date to YYYY-MM-DD or null.
     * TMDB may return partial dates (e.g. "2025", "2025-06") for unreleased films.
     *
     * @return string|null Full date (YYYY-MM-DD) or null if invalid/empty
     */
    private static function normalizeReleaseDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value.'-01';
        }

        if (preg_match('/^\d{4}$/', $value)) {
            return $value.'-01-01';
        }

        return null;
    }

    /**
     * Parse director and writers from TMDB credits crew array.
     *
     * @param  array<int, array<string, mixed>>  $crew
     * @return array{director: string|null, writers: array<int, string>}
     */
    private static function parseCredits(array $crew): array
    {
        $director = null;
        $writers = [];

        $writerJobs = ['Screenplay', 'Writer', 'Story', 'Original Story', 'Novel'];

        foreach ($crew as $member) {
            $job = $member['job'] ?? null;
            $name = $member['name'] ?? null;

            if (! $job || ! $name) {
                continue;
            }

            if ($job === 'Director' && $director === null) {
                $director = $name;
            }

            if (in_array($job, $writerJobs, true)) {
                $writers[] = $name;
            }
        }

        return [
            'director' => $director,
            'writers' => array_values(array_unique($writers)),
        ];
    }

    /**
     * Parse cast from TMDB credits.cast array. Top 20 by order, tolerant of missing keys.
     *
     * @param  array<int, array<string, mixed>>  $cast
     * @return array<int, array{tmdb_id: int, name: string, character: string|null, order: int, profile_path: string|null}>
     */
    private static function parseCast(array $cast): array
    {
        $parsed = [];
        foreach ($cast as $member) {
            $id = isset($member['id']) ? (int) $member['id'] : null;
            $name = isset($member['name']) ? trim((string) $member['name']) : '';
            $order = isset($member['order']) ? (int) $member['order'] : 999;
            if ($id === null || $name === '') {
                continue;
            }
            $parsed[] = [
                'tmdb_id' => $id,
                'name' => $name,
                'character' => isset($member['character']) && $member['character'] !== '' ? (string) $member['character'] : null,
                'order' => $order,
                'profile_path' => isset($member['profile_path']) && $member['profile_path'] !== '' ? (string) $member['profile_path'] : null,
            ];
        }
        usort($parsed, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return array_slice($parsed, 0, 20);
    }

    /**
     * Parse crew from TMDB credits.crew array. Unique by (name, job), tolerant of missing keys.
     *
     * @param  array<int, array<string, mixed>>  $crew
     * @return array<int, array{tmdb_id: int, name: string, job: string, department: string|null, profile_path: string|null}>
     */
    private static function parseCrew(array $crew): array
    {
        $seen = [];
        $parsed = [];
        foreach ($crew as $member) {
            $id = isset($member['id']) ? (int) $member['id'] : null;
            $name = isset($member['name']) ? trim((string) $member['name']) : '';
            $job = isset($member['job']) ? trim((string) $member['job']) : '';
            if ($id === null || $name === '' || $job === '') {
                continue;
            }
            $key = $name.'|'.$job;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $parsed[] = [
                'tmdb_id' => $id,
                'name' => $name,
                'job' => $job,
                'department' => isset($member['department']) && $member['department'] !== '' ? (string) $member['department'] : null,
                'profile_path' => isset($member['profile_path']) && $member['profile_path'] !== '' ? (string) $member['profile_path'] : null,
            ];
        }
        usort($parsed, fn (array $a, array $b) => strcmp($a['job'], $b['job']));

        return array_values($parsed);
    }

    /**
     * Get the release year from the release date.
     */
    public function getReleaseYear(): ?int
    {
        if (! $this->releaseDate) {
            return null;
        }

        return (int) substr($this->releaseDate, 0, 4);
    }

    /**
     * Check if this movie is upcoming.
     */
    public function isUpcoming(): bool
    {
        if (! $this->releaseDate) {
            return false;
        }

        return now()->lt($this->releaseDate);
    }

    /**
     * Get the first YouTube trailer URL if available.
     */
    public function getTrailerUrl(): ?string
    {
        $trailer = $this->videos
            ->filter(fn (TmdbVideoData $video) => $video->isYoutubeTrailer())
            ->first();

        return $trailer?->getYoutubeUrl();
    }

    /**
     * Get matched era from keywords.
     */
    public function getMatchedEra(): ?string
    {
        foreach ($this->keywords as $keyword) {
            $era = $keyword->matchEra();
            if ($era !== null) {
                return $era;
            }
        }

        return null;
    }

    /**
     * Convert to array suitable for Movie model creation.
     *
     * @return array<string, mixed>
     */
    public function toMovieAttributes(): array
    {
        return [
            'tmdb_id' => $this->id,
            'title' => $this->title,
            'synopsis' => $this->overview,
            'release_date' => $this->releaseDate,
            'release_year' => $this->getReleaseYear(),
            'runtime' => $this->runtime,
            'imdb_id' => $this->imdbId,
            'tmdb_vote_average' => $this->voteAverage,
            'tmdb_vote_count' => $this->voteCount,
            'director' => $this->director,
            'writers' => $this->writers,
            'production_status' => $this->productionStatus,
            'original_language' => $this->originalLanguage,
            'budget' => $this->budget,
            'revenue' => $this->revenue,
            'cast' => $this->cast,
            'crew' => $this->crew,
            'is_upcoming' => $this->isUpcoming(),
            'trailer_url' => $this->getTrailerUrl(),
        ];
    }

    /**
     * Convert to full array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'original_title' => $this->originalTitle,
            'overview' => $this->overview,
            'release_date' => $this->releaseDate,
            'runtime' => $this->runtime,
            'poster_path' => $this->posterPath,
            'backdrop_path' => $this->backdropPath,
            'imdb_id' => $this->imdbId,
            'vote_average' => $this->voteAverage,
            'vote_count' => $this->voteCount,
            'popularity' => $this->popularity,
            'genres' => $this->genres->map(fn (TmdbGenreData $g) => $g->toArray())->all(),
            'videos' => $this->videos->map(fn (TmdbVideoData $v) => $v->toArray())->all(),
            'keywords' => $this->keywords->map(fn (TmdbKeywordData $k) => $k->toArray())->all(),
        ];
    }
}
