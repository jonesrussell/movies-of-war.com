<?php

declare(strict_types=1);

namespace App\Data\X;

use Illuminate\Support\Collection;

/**
 * Data Transfer Object for X Search Response.
 */
readonly class XSearchResponse
{
    /**
     * @param  Collection<int, XTweetData>  $tweets
     * @param  Collection<string, XUserData>  $users  Map of user ID to user data
     */
    public function __construct(
        public Collection $tweets,
        public Collection $users,
        public ?string $nextToken = null,
        public int $resultCount = 0,
    ) {}

    /**
     * Create from X API search response.
     *
     * @param  array{data?: array<int, array<string, mixed>>, includes?: array{users?: array<int, array<string, mixed>>}, meta?: array{next_token?: string, result_count?: int}}  $response
     */
    public static function fromApiResponse(array $response): self
    {
        // Build users map first
        $users = collect($response['includes']['users'] ?? [])
            ->mapWithKeys(function (array $userData) {
                $user = XUserData::fromApiResponse($userData);

                return [$user->id => $user];
            });

        // Build tweets with author data
        $tweets = collect($response['data'] ?? [])
            ->map(function (array $tweetData) use ($users) {
                $author = $users->get($tweetData['author_id']);

                return XTweetData::fromApiResponse($tweetData, $author);
            });

        return new self(
            tweets: $tweets,
            users: $users,
            nextToken: $response['meta']['next_token'] ?? null,
            resultCount: $response['meta']['result_count'] ?? count($response['data'] ?? []),
        );
    }

    /**
     * Check if there are more results.
     */
    public function hasMoreResults(): bool
    {
        return $this->nextToken !== null;
    }

    /**
     * Check if the response is empty.
     */
    public function isEmpty(): bool
    {
        return $this->tweets->isEmpty();
    }

    /**
     * Get user by ID.
     */
    public function getUser(string $userId): ?XUserData
    {
        return $this->users->get($userId);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tweets' => $this->tweets->map(fn (XTweetData $t) => $t->toArray())->values()->all(),
            'users' => $this->users->map(fn (XUserData $u) => $u->toArray())->all(),
            'next_token' => $this->nextToken,
            'result_count' => $this->resultCount,
        ];
    }
}
