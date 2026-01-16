<?php

namespace App\Services;

use App\Models\XAnalytics;
use App\Models\XPost;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class XAnalyticsService
{
    public function __construct(
        protected XApiService $xApiService
    ) {}

    /**
     * Fetch and store metrics for a specific tweet.
     *
     * @param  string  $tweetId  The tweet ID from X API
     * @param  XPost|null  $xPost  Optional XPost model to associate with
     * @return XAnalytics The created/updated analytics record
     *
     * @throws Exception
     */
    public function fetchMetrics(string $tweetId, ?XPost $xPost = null): XAnalytics
    {
        try {
            $tweetData = $this->xApiService->getTweetMetrics($tweetId);
            $metrics = $tweetData['public_metrics'] ?? [];

            $analyticsData = [
                'tweet_id' => $tweetId,
                'x_post_id' => $xPost?->id,
                'impressions' => $metrics['impression_count'] ?? 0,
                'likes' => $metrics['like_count'] ?? 0,
                'retweets' => $metrics['retweet_count'] ?? 0,
                'replies' => $metrics['reply_count'] ?? 0,
                'bookmarks' => $metrics['bookmark_count'] ?? 0,
                'quotes' => $metrics['quote_count'] ?? 0,
                'profile_clicks' => $metrics['profile_clicks'] ?? 0,
                'link_clicks' => $metrics['link_clicks'] ?? 0,
                'recorded_at' => now(),
            ];

            // Create new analytics record (for historical tracking)
            return XAnalytics::create($analyticsData);
        } catch (Exception $e) {
            Log::error('X Analytics: Failed to fetch metrics', [
                'tweet_id' => $tweetId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync metrics for all published posts.
     *
     * @param  int  $limit  Maximum number of posts to sync
     * @return int Number of posts synced
     */
    public function syncPublishedPosts(int $limit = 50): int
    {
        $posts = XPost::published()
            ->whereNotNull('x_post_id')
            ->limit($limit)
            ->get();

        $synced = 0;

        foreach ($posts as $post) {
            try {
                $this->fetchMetrics($post->x_post_id, $post);
                $synced++;

                // Rate limiting: sleep between requests
                usleep(250000); // 0.25 seconds
            } catch (Exception $e) {
                Log::warning('X Analytics: Failed to sync post', [
                    'x_post_id' => $post->id,
                    'tweet_id' => $post->x_post_id,
                    'error' => $e->getMessage(),
                ]);

                // Continue with next post
            }
        }

        return $synced;
    }

    /**
     * Get performance report for a date range.
     *
     * @param  Carbon|null  $startDate
     * @param  Carbon|null  $endDate
     * @return array Performance metrics
     */
    public function getPerformanceReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = XAnalytics::query();

        if ($startDate) {
            $query->where('recorded_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('recorded_at', '<=', $endDate);
        }

        // Get latest metrics for each tweet (to avoid duplicates)
        $latestAnalytics = $query->latest('recorded_at')
            ->get()
            ->unique('tweet_id');

        return [
            'total_impressions' => $latestAnalytics->sum('impressions'),
            'total_likes' => $latestAnalytics->sum('likes'),
            'total_retweets' => $latestAnalytics->sum('retweets'),
            'total_replies' => $latestAnalytics->sum('replies'),
            'total_bookmarks' => $latestAnalytics->sum('bookmarks'),
            'total_quotes' => $latestAnalytics->sum('quotes'),
            'avg_engagement_rate' => $this->calculateEngagementRate($latestAnalytics),
            'total_posts' => $latestAnalytics->count(),
        ];
    }

    /**
     * Get top performing posts.
     *
     * @param  int  $limit
     * @param  string  $metric  Sort by: impressions, likes, retweets, etc.
     * @return Collection Top performing posts with analytics
     */
    public function getTopPerformers(int $limit = 10, string $metric = 'impressions'): Collection
    {
        $latestAnalytics = XAnalytics::query()
            ->latest('recorded_at')
            ->get()
            ->unique('tweet_id')
            ->sortByDesc($metric)
            ->take($limit);

        // Load related posts
        return $latestAnalytics->load('xPost');
    }

    /**
     * Calculate engagement rate (likes + retweets + replies) / impressions.
     *
     * @param  Collection  $analytics
     * @return float Engagement rate percentage
     */
    protected function calculateEngagementRate(Collection $analytics): float
    {
        $totalImpressions = $analytics->sum('impressions');
        $totalEngagement = $analytics->sum('likes') +
            $analytics->sum('retweets') +
            $analytics->sum('replies');

        if ($totalImpressions === 0) {
            return 0.0;
        }

        return round(($totalEngagement / $totalImpressions) * 100, 2);
    }
}
