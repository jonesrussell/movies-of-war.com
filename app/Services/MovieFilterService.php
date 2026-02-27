<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Movie;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MovieFilterService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache key prefix.
     */
    private const CACHE_PREFIX = 'movie_filters';

    /**
     * Get all filter options with caching.
     *
     * @return array{countries: Collection, conflicts: Collection, years: Collection, tags: Collection, eraTags: Collection}
     */
    public function getFilterOptions(): array
    {
        return [
            'countries' => $this->getCountries(),
            'conflicts' => $this->getConflicts(),
            'years' => $this->getYears(),
            'tags' => $this->getTags(),
            'eraTags' => $this->getEraTags(),
        ];
    }

    /**
     * Get distinct countries with caching.
     *
     * @return Collection<int, string>
     */
    public function getCountries(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.':countries',
            self::CACHE_TTL,
            fn () => Movie::query()
                ->published()
                ->distinct()
                ->pluck('country')
                ->filter()
                ->sort()
                ->values()
        );
    }

    /**
     * Get distinct conflicts with caching.
     *
     * @return Collection<int, string>
     */
    public function getConflicts(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.':conflicts',
            self::CACHE_TTL,
            fn () => Movie::query()
                ->published()
                ->distinct()
                ->pluck('conflict')
                ->filter()
                ->sort()
                ->values()
        );
    }

    /**
     * Get distinct years with caching.
     *
     * @return Collection<int, int>
     */
    public function getYears(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.':years',
            self::CACHE_TTL,
            fn () => Movie::query()
                ->published()
                ->distinct()
                ->pluck('release_year')
                ->filter()
                ->sort()
                ->reverse()
                ->values()
        );
    }

    /**
     * Get all tags with caching.
     *
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.':tags',
            self::CACHE_TTL,
            fn () => Tag::all()
        );
    }

    /**
     * Get era tags that have at least one published movie.
     *
     * @return Collection<int, Tag>
     */
    public function getEraTags(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.':era_tags',
            self::CACHE_TTL,
            fn () => Tag::eras()
                ->whereHas('movies', fn ($q) => $q->published())
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Clear all filter caches.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX.':countries');
        Cache::forget(self::CACHE_PREFIX.':conflicts');
        Cache::forget(self::CACHE_PREFIX.':years');
        Cache::forget(self::CACHE_PREFIX.':tags');
        Cache::forget(self::CACHE_PREFIX.':era_tags');
    }

    /**
     * Get cache key prefix for external use.
     */
    public static function getCachePrefix(): string
    {
        return self::CACHE_PREFIX;
    }
}
