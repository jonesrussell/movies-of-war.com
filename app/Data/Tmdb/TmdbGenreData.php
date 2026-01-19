<?php

declare(strict_types=1);

namespace App\Data\Tmdb;

/**
 * Data Transfer Object for TMDB Genre.
 */
readonly class TmdbGenreData
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    /**
     * Create from TMDB API response.
     *
     * @param  array{id: int, name: string}  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }

    /**
     * Convert to array for persistence.
     *
     * @return array{id: int, name: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
