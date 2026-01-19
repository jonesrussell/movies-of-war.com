<?php

declare(strict_types=1);

namespace App\Data\Tmdb;

/**
 * Data Transfer Object for TMDB Keyword.
 */
readonly class TmdbKeywordData
{
    /**
     * Era keyword patterns for matching historical periods.
     */
    private const ERA_PATTERNS = [
        'world war i' => 'WWI',
        'world war ii' => 'WWII',
        'world war 2' => 'WWII',
        'ww2' => 'WWII',
        'ww1' => 'WWI',
        'vietnam war' => 'Vietnam War',
        'vietnam' => 'Vietnam War',
        'korean war' => 'Korean War',
        'civil war' => 'Civil War',
        'american civil war' => 'American Civil War',
        'revolutionary war' => 'Revolutionary War',
        'gulf war' => 'Gulf War',
        'iraq war' => 'Iraq War',
        'afghanistan' => 'Afghanistan War',
        'cold war' => 'Cold War',
        'napoleonic' => 'Napoleonic Wars',
        'medieval' => 'Medieval',
        'ancient' => 'Ancient',
    ];

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
     * Attempt to match this keyword to a historical era.
     * Returns the matched era name or null if no match.
     */
    public function matchEra(): ?string
    {
        $normalizedName = strtolower($this->name);

        foreach (self::ERA_PATTERNS as $pattern => $era) {
            if (str_contains($normalizedName, $pattern)) {
                return $era;
            }
        }

        return null;
    }

    /**
     * Check if this keyword matches any historical era.
     */
    public function isEraKeyword(): bool
    {
        return $this->matchEra() !== null;
    }

    /**
     * Convert to array.
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
