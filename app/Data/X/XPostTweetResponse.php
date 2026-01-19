<?php

declare(strict_types=1);

namespace App\Data\X;

/**
 * Data Transfer Object for X Post Tweet Response.
 */
readonly class XPostTweetResponse
{
    public function __construct(
        public string $id,
        public string $text,
    ) {}

    /**
     * Create from X API post tweet response.
     *
     * @param  array{id: string, text: string}  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'],
            text: $data['text'] ?? '',
        );
    }

    /**
     * Convert to array (for backward compatibility).
     *
     * @return array{id: string, text: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
        ];
    }
}
