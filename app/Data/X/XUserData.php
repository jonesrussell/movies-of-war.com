<?php

declare(strict_types=1);

namespace App\Data\X;

/**
 * Data Transfer Object for X User.
 */
readonly class XUserData
{
    public function __construct(
        public string $id,
        public string $username,
        public ?string $name = null,
    ) {}

    /**
     * Create from X API response.
     *
     * @param  array{id: string, username: string, name?: string}  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            id: $data['id'],
            username: $data['username'],
            name: $data['name'] ?? null,
        );
    }

    /**
     * Get profile URL.
     */
    public function getProfileUrl(): string
    {
        return "https://x.com/{$this->username}";
    }

    /**
     * Get display name (name or username).
     */
    public function getDisplayName(): string
    {
        return $this->name ?? $this->username;
    }

    /**
     * Convert to array.
     *
     * @return array{id: string, username: string, name: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
        ];
    }
}
