<?php

namespace App\Services;

use App\Models\XCuratedPost;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class XContentDiscoveryService
{
    public function __construct(
        protected XApiService $xApiService
    ) {}

    /**
     * Discover high-quality war movie content.
     *
     * @param  array  $filters  Search filters (keywords, min_likes, etc.)
     * @return int Number of posts discovered
     *
     * @throws Exception
     */
    public function discoverContent(array $filters = []): int
    {
        try {
            // Default search queries for war movie content
            $queries = $filters['queries'] ?? [
                '#WarMovies',
                '#WWIIMovies',
                '#VietnamMovies',
                'war movie review',
                'best war films',
            ];

            $minLikes = $filters['min_likes'] ?? 10;
            $maxResults = $filters['max_results'] ?? 50;
            $discovered = 0;

            foreach ($queries as $query) {
                try {
                    $response = $this->xApiService->searchTweets($query, [
                        'max_results' => min(20, $maxResults),
                        'tweet.fields' => 'created_at,author_id,public_metrics,text,attachments',
                        'expansions' => 'author_id,attachments.media_keys',
                        'user.fields' => 'username,name',
                        'media.fields' => 'url,preview_image_url',
                    ]);

                    $tweets = $response['data'] ?? [];
                    $users = collect($response['includes']['users'] ?? [])->keyBy('id');
                    $media = collect($response['includes']['media'] ?? [])->keyBy('media_key');

                    foreach ($tweets as $tweet) {
                        $metrics = $tweet['public_metrics'] ?? [];
                        $likeCount = $metrics['like_count'] ?? 0;

                        // Filter by minimum likes
                        if ($likeCount < $minLikes) {
                            continue;
                        }

                        // Check if already curated
                        $existing = XCuratedPost::where('tweet_id', $tweet['id'])->first();
                        if ($existing) {
                            continue;
                        }

                        $authorId = $tweet['author_id'] ?? null;
                        $author = $authorId ? $users->get($authorId) : null;

                        // Extract media URLs
                        $mediaUrls = [];
                        if (isset($tweet['attachments']['media_keys'])) {
                            foreach ($tweet['attachments']['media_keys'] as $mediaKey) {
                                $mediaItem = $media->get($mediaKey);
                                if ($mediaItem) {
                                    $url = $mediaItem['url'] ?? $mediaItem['preview_image_url'] ?? null;
                                    if ($url) {
                                        $mediaUrls[] = $url;
                                    }
                                }
                            }
                        }

                        $this->saveCuratedPost([
                            'tweet_id' => $tweet['id'],
                            'author_username' => $author['username'] ?? 'unknown',
                            'content' => $tweet['text'] ?? '',
                            'media_urls' => $mediaUrls,
                            'like_count' => $likeCount,
                            'retweet_count' => $metrics['retweet_count'] ?? 0,
                            'created_at' => isset($tweet['created_at']) ? Carbon::parse($tweet['created_at']) : now(),
                        ]);

                        $discovered++;
                    }

                    // Rate limiting: sleep between queries
                    usleep(500000); // 0.5 seconds
                } catch (Exception $e) {
                    Log::warning('X Content Discovery: Failed to search query', [
                        'query' => $query,
                        'error' => $e->getMessage(),
                    ]);

                    // Continue with next query
                }
            }

            return $discovered;
        } catch (Exception $e) {
            Log::error('X Content Discovery: Failed to discover content', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Save a curated post.
     *
     * @param  array  $tweetData  Tweet data from API
     * @return XCuratedPost
     */
    public function saveCuratedPost(array $tweetData): XCuratedPost
    {
        return XCuratedPost::create([
            'tweet_id' => $tweetData['tweet_id'],
            'author_username' => $tweetData['author_username'] ?? 'unknown',
            'content' => $tweetData['content'] ?? '',
            'media_urls' => $tweetData['media_urls'] ?? [],
            'like_count' => $tweetData['like_count'] ?? 0,
            'retweet_count' => $tweetData['retweet_count'] ?? 0,
            'discovered_at' => $tweetData['created_at'] ?? now(),
        ]);
    }

    /**
     * Get curated feed.
     *
     * @param  int  $limit
     * @param  array  $filters  Filters (featured, high_engagement, etc.)
     * @return Collection Curated posts
     */
    public function getCuratedFeed(int $limit = 20, array $filters = []): Collection
    {
        $query = XCuratedPost::query();

        if ($filters['featured'] ?? false) {
            $query->featured();
        }

        if ($filters['high_engagement'] ?? false) {
            $query->highEngagement();
        }

        if ($filters['recent'] ?? false) {
            $query->recent(7);
        }

        return $query->orderBy('discovered_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
