<?php

declare(strict_types=1);

namespace App\Data\Tmdb;

use Illuminate\Support\Collection;

/**
 * Data Transfer Object for TMDB Discover Response.
 */
readonly class TmdbDiscoverResponse
{
    /**
     * @param  Collection<int, array<string, mixed>>  $results
     */
    public function __construct(
        public Collection $results,
        public int $totalPages = 1,
        public int $totalResults = 0,
        public int $page = 1,
    ) {}

    /**
     * Create from TMDB API discover response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            results: collect($data['results'] ?? []),
            totalPages: $data['total_pages'] ?? 1,
            totalResults: $data['total_results'] ?? 0,
            page: $data['page'] ?? 1,
        );
    }

    /**
     * Check if response is empty.
     */
    public function isEmpty(): bool
    {
        return $this->results->isEmpty();
    }

    /**
     * Get movie IDs from results.
     *
     * @return Collection<int, int>
     */
    public function getMovieIds(): Collection
    {
        return $this->results->pluck('id');
    }

    /**
     * Convert to array (for backward compatibility).
     *
     * @return array{results: array<int, array<string, mixed>>, total_pages: int, total_results: int}
     */
    public function toArray(): array
    {
        return [
            'results' => $this->results->all(),
            'total_pages' => $this->totalPages,
            'total_results' => $this->totalResults,
        ];
    }
}
