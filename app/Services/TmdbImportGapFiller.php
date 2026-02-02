<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

class TmdbImportGapFiller
{
    /**
     * Sort discovery strategies by gap score (ascending movie count per bucket).
     * Strategies with fewer movies in their bucket come first.
     *
     * @param  array<int, array<string, mixed>>  $strategies
     * @return array<int, array<string, mixed>> Sorted strategies
     */
    public function sortStrategiesByGap(array $strategies): array
    {
        if (! config('tmdb.import.gap_filling_enabled', true)) {
            return $strategies;
        }

        $bucketMap = config('tmdb.import.strategy_bucket_map', []);
        $strategiesWithCounts = [];

        foreach ($strategies as $strategy) {
            $key = $strategy['key'] ?? null;
            $bucket = $bucketMap[$key] ?? null;

            if ($bucket === null) {
                $count = PHP_INT_MAX;
            } else {
                $count = $this->getBucketMovieCount($bucket);
            }

            $strategiesWithCounts[] = [
                'strategy' => $strategy,
                'count' => $count,
            ];
        }

        usort($strategiesWithCounts, fn (array $a, array $b) => $a['count'] <=> $b['count']);

        return array_map(fn (array $item) => $item['strategy'], $strategiesWithCounts);
    }

    /**
     * Get movie count for a bucket (tag slug) with caching.
     */
    protected function getBucketMovieCount(string $bucket): int
    {
        $cacheKey = "tmdb_import:bucket_count:{$bucket}";
        $ttl = 3600;

        return Cache::remember($cacheKey, $ttl, function () use ($bucket) {
            $tag = Tag::where('slug', $bucket)->first();

            return $tag ? $tag->movies()->count() : 0;
        });
    }
}
