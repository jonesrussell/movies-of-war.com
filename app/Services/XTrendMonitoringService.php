<?php

namespace App\Services;

use App\Models\XTrendKeyword;
use App\Models\XTrendResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class XTrendMonitoringService
{
    public function __construct(
        protected XApiService $xApiService
    ) {}

    /**
     * Search for a specific keyword and store results.
     *
     * @param  int  $maxResults  Maximum results to fetch
     * @return int Number of results found
     *
     * @throws Exception
     */
    public function searchKeyword(XTrendKeyword $keyword, int $maxResults = 10): int
    {
        try {
            // Build search query based on keyword type
            $query = $this->buildSearchQuery($keyword);

            // Search tweets
            $response = $this->xApiService->searchTweets($query, [
                'max_results' => min($maxResults, 100), // API limit
            ]);

            $tweets = $response['data'] ?? [];
            $users = collect($response['includes']['users'] ?? [])->keyBy('id');

            $resultsCount = 0;

            foreach ($tweets as $tweet) {
                $authorId = $tweet['author_id'] ?? null;
                $author = $authorId ? $users->get($authorId) : null;
                $metrics = $tweet['public_metrics'] ?? [];

                // Check if result already exists
                $existing = XTrendResult::where('tweet_id', $tweet['id'])->first();

                if (! $existing) {
                    XTrendResult::create([
                        'trend_keyword_id' => $keyword->id,
                        'tweet_id' => $tweet['id'],
                        'author_username' => $author['username'] ?? 'unknown',
                        'content' => $tweet['text'] ?? '',
                        'like_count' => $metrics['like_count'] ?? 0,
                        'retweet_count' => $metrics['retweet_count'] ?? 0,
                        'reply_count' => $metrics['reply_count'] ?? 0,
                        'tweet_created_at' => isset($tweet['created_at']) ? Carbon::parse($tweet['created_at']) : now(),
                        'url' => "https://x.com/{$author['username']}/status/{$tweet['id']}",
                    ]);

                    $resultsCount++;
                }
            }

            // Update keyword stats
            $keyword->markSearched($resultsCount);

            return $resultsCount;
        } catch (Exception $e) {
            Log::error('X Trend Monitoring: Failed to search keyword', [
                'keyword_id' => $keyword->id,
                'keyword' => $keyword->keyword,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Monitor all active keywords.
     *
     * @return int Total results found
     */
    public function monitorAllActiveKeywords(): int
    {
        $keywords = XTrendKeyword::active()->get();
        $totalResults = 0;

        foreach ($keywords as $keyword) {
            try {
                $results = $this->searchKeyword($keyword, 10);
                $totalResults += $results;

                // Rate limiting: sleep between searches
                usleep(500000); // 0.5 seconds
            } catch (Exception $e) {
                Log::warning('X Trend Monitoring: Failed to monitor keyword', [
                    'keyword_id' => $keyword->id,
                    'error' => $e->getMessage(),
                ]);

                // Continue with next keyword
            }
        }

        return $totalResults;
    }

    /**
     * Get recent trends.
     *
     * @return Collection Recent trend results
     */
    public function getRecentTrends(int $limit = 20): Collection
    {
        return XTrendResult::query()
            ->with('keyword')
            ->recent(7)
            ->orderBy('like_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Build search query based on keyword type.
     *
     * @return string Search query
     */
    protected function buildSearchQuery(XTrendKeyword $keyword): string
    {
        $keywordValue = $keyword->keyword;

        return match ($keyword->type) {
            XTrendKeyword::TYPE_HASHTAG => $keywordValue[0] === '#' ? $keywordValue : "#{$keywordValue}",
            XTrendKeyword::TYPE_PHRASE => '"'.$keywordValue.'"',
            default => $keywordValue,
        };
    }
}
