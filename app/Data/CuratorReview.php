<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Immutable DTO for a filesystem-based curator review.
 *
 * @implements Arrayable<string, mixed>
 */
readonly class CuratorReview implements Arrayable
{
    /**
     * @param  string[]  $starring
     */
    public function __construct(
        public string $title,
        public int $year,
        public float $rating,
        public ?string $director,
        public array $starring,
        public ?int $runtime,
        public string $slug,
        public bool $hasSpoilers,
        public string $contentHtml,
        public string $contentExcerpt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'year' => $this->year,
            'rating' => $this->rating,
            'director' => $this->director,
            'starring' => $this->starring,
            'runtime' => $this->runtime,
            'slug' => $this->slug,
            'has_spoilers' => $this->hasSpoilers,
            'content_html' => $this->contentHtml,
            'content_excerpt' => $this->contentExcerpt,
        ];
    }
}
