<?php

declare(strict_types=1);

namespace App\Data\X;

use DateTimeImmutable;

/**
 * Data Transfer Object for X Tweet.
 */
readonly class XTweetData
{
    public function __construct(
        public string $id,
        public string $text,
        public string $authorId,
        public ?DateTimeImmutable $createdAt,
        public ?XTweetMetrics $metrics,
        public ?XUserData $author = null,
    ) {}

    /**
     * Create from X API response.
     *
     * @param  array{id: string, text: string, author_id: string, created_at?: string, public_metrics?: array<string, int>}  $data
     * @param  XUserData|null  $author  Optional author data from includes
     */
    public static function fromApiResponse(array $data, ?XUserData $author = null): self
    {
        $createdAt = null;
        if (isset($data['created_at'])) {
            $createdAt = new DateTimeImmutable($data['created_at']);
        }

        $metrics = null;
        if (isset($data['public_metrics'])) {
            $metrics = XTweetMetrics::fromApiResponse($data['public_metrics']);
        }

        return new self(
            id: $data['id'],
            text: $data['text'],
            authorId: $data['author_id'],
            createdAt: $createdAt,
            metrics: $metrics,
            author: $author,
        );
    }

    /**
     * Get tweet URL.
     */
    public function getTweetUrl(): string
    {
        $username = $this->author?->username ?? 'i';

        return "https://x.com/{$username}/status/{$this->id}";
    }

    /**
     * Check if tweet has metrics data.
     */
    public function hasMetrics(): bool
    {
        return $this->metrics !== null;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'author_id' => $this->authorId,
            'created_at' => $this->createdAt?->format('c'),
            'public_metrics' => $this->metrics?->toArray(),
            'author' => $this->author?->toArray(),
            'tweet_url' => $this->getTweetUrl(),
        ];
    }
}
