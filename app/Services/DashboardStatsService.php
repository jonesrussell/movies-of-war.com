<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeaturedSlot;
use App\Models\Movie;
use App\Models\Tag;
use App\Models\User;
use JonesRussell\XSuite\Models\XPost;
use JonesRussell\XSuite\Models\XTrendKeyword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Cache key prefix.
     */
    private const CACHE_PREFIX = 'dashboard_stats';

    public function __construct(
        protected \JonesRussell\XSuite\Services\XAnalyticsService $analyticsService
    ) {}

    /**
     * Get core movie stats with caching.
     *
     * @return array{movies: int, drafts: int, archived: int, tags: int, activeFeatures: int}
     */
    public function getMovieStats(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . ':movie_stats',
            self::CACHE_TTL,
            function () {
                // Single query with conditional counts
                $counts = Movie::query()
                    ->select([
                        DB::raw("COUNT(CASE WHEN status = 'published' THEN 1 END) as published_count"),
                        DB::raw("COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count"),
                        DB::raw("COUNT(CASE WHEN status = 'archived' THEN 1 END) as archived_count"),
                    ])
                    ->first();

                return [
                    'movies' => (int) $counts->published_count,
                    'drafts' => (int) $counts->draft_count,
                    'archived' => (int) $counts->archived_count,
                    'tags' => Tag::count(),
                    'activeFeatures' => FeaturedSlot::active()->count(),
                ];
            }
        );
    }

    /**
     * Get X/Twitter stats with caching.
     *
     * @return array{published_posts: int, scheduled_posts: int, failed_posts: int, total_impressions: int, active_keywords: int, engagement_rate: float}
     */
    public function getXStats(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . ':x_stats',
            self::CACHE_TTL,
            function () {
                // Single query with conditional counts
                $xPostCounts = XPost::query()
                    ->select([
                        DB::raw("COUNT(CASE WHEN status = 'published' THEN 1 END) as published_count"),
                        DB::raw("COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled_count"),
                        DB::raw("COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count"),
                    ])
                    ->first();

                $xPerformanceReport = $this->analyticsService->getPerformanceReport(now()->subDays(30), now());

                return [
                    'published_posts' => (int) $xPostCounts->published_count,
                    'scheduled_posts' => (int) $xPostCounts->scheduled_count,
                    'failed_posts' => (int) $xPostCounts->failed_count,
                    'total_impressions' => $xPerformanceReport['total_impressions'] ?? 0,
                    'active_keywords' => XTrendKeyword::active()->count(),
                    'engagement_rate' => $xPerformanceReport['average_engagement_rate'] ?? 0.0,
                ];
            }
        );
    }

    /**
     * Get combined stats for admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getAdminStats(): array
    {
        $movieStats = $this->getMovieStats();
        $xStats = $this->getXStats();

        return array_merge($movieStats, [
            'tmdbDrafts' => $movieStats['drafts'],
            'x_published_posts' => $xStats['published_posts'],
            'x_scheduled_posts' => $xStats['scheduled_posts'],
            'x_failed_posts' => $xStats['failed_posts'],
            'x_total_impressions' => $xStats['total_impressions'],
            'x_active_keywords' => $xStats['active_keywords'],
        ]);
    }

    /**
     * Get stats for non-admin users.
     *
     * @return array{movies_total: int, watchlist_count: int, reviews_count: int}
     */
    public function getUserStats(User $user): array
    {
        $movieStats = $this->getMovieStats();

        return [
            'movies_total' => $movieStats['movies'],
            'watchlist_count' => $user->watchlist()->count(),
            'reviews_count' => $user->reviews()->count(),
        ];
    }

    /**
     * Clear all dashboard stats caches.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . ':movie_stats');
        Cache::forget(self::CACHE_PREFIX . ':x_stats');
    }

    /**
     * Get cache key prefix for external use.
     */
    public static function getCachePrefix(): string
    {
        return self::CACHE_PREFIX;
    }
}
