<?php

declare(strict_types=1);

namespace App\Data\Tmdb;

/**
 * Data Transfer Object for TMDB Video.
 */
readonly class TmdbVideoData
{
    public function __construct(
        public string $id,
        public string $key,
        public string $name,
        public string $site,
        public string $type,
        public bool $official,
    ) {}

    /**
     * Create from TMDB API response.
     *
     * @param  array{id: string, key: string, name: string, site: string, type: string, official?: bool}  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'],
            key: $data['key'],
            name: $data['name'],
            site: $data['site'],
            type: $data['type'],
            official: $data['official'] ?? false,
        );
    }

    /**
     * Check if this video is a YouTube trailer.
     */
    public function isYoutubeTrailer(): bool
    {
        return $this->site === 'YouTube' && $this->type === 'Trailer';
    }

    /**
     * Get YouTube URL if this is a YouTube video.
     */
    public function getYoutubeUrl(): ?string
    {
        if ($this->site !== 'YouTube') {
            return null;
        }

        return "https://www.youtube.com/watch?v={$this->key}";
    }

    /**
     * Convert to array.
     *
     * @return array{id: string, key: string, name: string, site: string, type: string, official: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'site' => $this->site,
            'type' => $this->type,
            'official' => $this->official,
        ];
    }
}
