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

        return new self(
            id: $data['id'],
            title: $data['title'],
            originalTitle: $data['original_title'] ?? null,
            overview: $data['overview'] ?? null,
            releaseDate: $data['release_date'] ?? null,
            runtime: $data['runtime'] ?? null,
            posterPath: $data['poster_path'] ?? null,
            backdropPath: $data['backdrop_path'] ?? null,
            imdbId: $data['imdb_id'] ?? null,
            voteAverage: $data['vote_average'] ?? null,
            voteCount: $data['vote_count'] ?? null,
            popularity: $data['popularity'] ?? null,
            genres: collect($data['genres'] ?? [])
                ->map(fn (array $genre) => TmdbGenreData::fromApiResponse($genre)),
            videos: collect($data['videos']['results'] ?? [])
                ->map(fn (array $video) => TmdbVideoData::fromApiResponse($video)),
            keywords: collect($data['keywords']['keywords'] ?? [])
                ->map(fn (array $keyword) => TmdbKeywordData::fromApiResponse($keyword)),
            director: $credits['director'],
            writers: $credits['writers'],
        );
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
