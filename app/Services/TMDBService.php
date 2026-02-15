<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Tmdb\TmdbDiscoverResponse;
use App\Data\Tmdb\TmdbMovieData;
use App\Data\Tmdb\TmdbPersonData;
use App\Data\Tmdb\TmdbSearchResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TMDBService
{
    protected ?string $apiKey;

    protected ?string $baseUrl;

    protected ?string $imageBaseUrl;

    public function __construct()
    {
        $this->apiKey = config('tmdb.api_key');
        $this->baseUrl = config('tmdb.base_url');
        $this->imageBaseUrl = config('tmdb.image_base_url');
    }

    public function searchMovies(string $query): array
    {
        $response = Http::get("{$this->baseUrl}/search/movie", [
            'api_key' => $this->apiKey,
            'query' => $query,
        ]);

        return $response->json()['results'] ?? [];
    }

    /**
     * Search movies and return as DTO.
     */
    public function searchMoviesAsDto(string $query): TmdbSearchResponse
    {
        $response = Http::get("{$this->baseUrl}/search/movie", [
            'api_key' => $this->apiKey,
            'query' => $query,
        ]);

        $data = $response->json();

        return TmdbSearchResponse::fromApiResponse($data);
    }

    /**
     * Search keywords by name. Returns array of {id, name}.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function searchKeywords(string $query): array
    {
        $response = Http::get("{$this->baseUrl}/search/keyword", [
            'api_key' => $this->apiKey,
            'query' => $query,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return collect($data['results'] ?? [])
            ->map(fn (array $keyword) => [
                'id' => $keyword['id'],
                'name' => $keyword['name'],
            ])
            ->all();
    }

    /**
     * Resolve keyword names to TMDB keyword IDs with caching.
     *
     * @param  array<int, string>  $names
     * @return array<int, int> Array of keyword IDs
     */
    public function resolveKeywordIds(array $names): array
    {
        $ttl = config('tmdb.import.keyword_cache_ttl_seconds', 86400);
        $ids = [];

        foreach ($names as $name) {
            $cacheKey = 'tmdb_keyword_id:'.md5(strtolower($name));

            $keywordId = Cache::remember($cacheKey, $ttl, function () use ($name) {
                $results = $this->searchKeywords($name);

                if (empty($results)) {
                    return null;
                }

                return $results[0]['id'];
            });

            if ($keywordId !== null) {
                $ids[] = $keywordId;
            }
        }

        return $ids;
    }

    public function getMovieDetails(int $tmdbId): ?array
    {
        $response = Http::get("{$this->baseUrl}/movie/{$tmdbId}", [
            'api_key' => $this->apiKey,
            'append_to_response' => 'videos,keywords,credits',
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get movie details from TMDB and return as DTO.
     */
    public function getMovieDetailsAsDto(int $tmdbId): ?TmdbMovieData
    {
        $data = $this->getMovieDetails($tmdbId);

        if ($data === null) {
            return null;
        }

        return TmdbMovieData::fromApiResponse($data);
    }

    /**
     * Get person details from TMDB and return as DTO.
     */
    public function getPersonDetailsAsDto(int $tmdbId): ?TmdbPersonData
    {
        $response = Http::get("{$this->baseUrl}/person/{$tmdbId}", [
            'api_key' => $this->apiKey,
            'append_to_response' => 'movie_credits,images',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (! is_array($data)) {
            return null;
        }

        return TmdbPersonData::fromApiResponse($data);
    }

    public function getProfileImageUrl(?string $profilePath, string $size = 'w185'): ?string
    {
        if (! $profilePath) {
            return null;
        }

        return "{$this->imageBaseUrl}/{$size}{$profilePath}";
    }

    public function discoverWarMovies(int $page = 1, bool $upcoming = false): array
    {
        $dto = $this->discoverWarMoviesAsDto($page, $upcoming);

        return $dto->toArray();
    }

    /**
     * Discover war movies and return as DTO.
     */
    public function discoverWarMoviesAsDto(int $page = 1, bool $upcoming = false): TmdbDiscoverResponse
    {
        $query = [
            'api_key' => $this->apiKey,
            'with_genres' => '10752', // War genre
            'with_original_language' => 'en', // English only
            'page' => $page,
        ];

        if ($upcoming) {
            $query['primary_release_date.gte'] = now()->toDateString();
            $query['sort_by'] = 'primary_release_date.asc';
        } else {
            $query['sort_by'] = 'vote_average.desc';
            $query['vote_count.gte'] = 100;
        }

        $response = Http::get("{$this->baseUrl}/discover/movie", $query);

        $data = $response->json();

        return TmdbDiscoverResponse::fromApiResponse($data);
    }

    /**
     * Discover movies with flexible filters.
     *
     * @param  array<int, int>  $genreIds
     * @param  array<int, int>  $keywordIds
     */
    public function discoverMoviesAsDto(
        int $page = 1,
        ?array $genreIds = null,
        ?array $keywordIds = null,
        ?int $minVoteCount = null,
        ?float $minVoteAverage = null,
        bool $upcoming = false,
        string $language = 'en'
    ): TmdbDiscoverResponse {
        $query = [
            'api_key' => $this->apiKey,
            'with_original_language' => $language,
            'page' => $page,
        ];

        if ($genreIds !== null && count($genreIds) > 0) {
            $query['with_genres'] = implode(',', $genreIds);
        }

        if ($keywordIds !== null && count($keywordIds) > 0) {
            $query['with_keywords'] = implode('|', $keywordIds);
        }

        if ($minVoteCount !== null) {
            $query['vote_count.gte'] = $minVoteCount;
        }

        if ($minVoteAverage !== null) {
            $query['vote_average.gte'] = $minVoteAverage;
        }

        if ($upcoming) {
            $query['primary_release_date.gte'] = now()->toDateString();
            $query['sort_by'] = 'primary_release_date.asc';
        } else {
            $query['sort_by'] = 'vote_average.desc';
        }

        $response = Http::get("{$this->baseUrl}/discover/movie", $query);

        $data = $response->json();

        return TmdbDiscoverResponse::fromApiResponse($data);
    }

    /**
     * Get popular movies (paginated). Same response shape as discover.
     */
    public function getPopularMoviesAsDto(int $page = 1): TmdbDiscoverResponse
    {
        $response = Http::get("{$this->baseUrl}/movie/popular", [
            'api_key' => $this->apiKey,
            'page' => $page,
        ]);

        $data = $response->successful() ? $response->json() : [];

        return TmdbDiscoverResponse::fromApiResponse($data);
    }

    /**
     * Get trending movies (paginated). Maps response to same shape as discover.
     *
     * @param  'day'|'week'  $window
     */
    public function getTrendingMoviesAsDto(int $page = 1, string $window = 'day'): TmdbDiscoverResponse
    {
        $response = Http::get("{$this->baseUrl}/trending/movie/{$window}", [
            'api_key' => $this->apiKey,
            'page' => $page,
        ]);

        if (! $response->successful()) {
            return new TmdbDiscoverResponse(collect(), 1, 0, 1);
        }

        $data = $response->json();
        if (! isset($data['results'])) {
            return new TmdbDiscoverResponse(collect(), 1, 0, 1);
        }

        return TmdbDiscoverResponse::fromApiResponse($data);
    }

    /**
     * Get movie IDs that changed in the given date range. Paginates through all pages.
     * TMDB allows up to 14 days. Defaults to last 24 hours if no dates given.
     *
     * @return array<int, int>
     */
    public function getMovieChangeIds(?string $startDate = null, ?string $endDate = null): array
    {
        $ids = [];
        $page = 1;

        do {
            $query = [
                'api_key' => $this->apiKey,
                'page' => $page,
            ];
            if ($startDate !== null) {
                $query['start_date'] = $startDate;
            }
            if ($endDate !== null) {
                $query['end_date'] = $endDate;
            }

            $response = Http::get("{$this->baseUrl}/movie/changes", $query);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $results = $data['results'] ?? [];
            foreach ($results as $item) {
                $id = $item['id'] ?? null;
                if (is_int($id)) {
                    $ids[] = $id;
                }
            }

            $totalPages = $data['total_pages'] ?? 1;
            $page++;
        } while ($page <= $totalPages);

        return $ids;
    }

    public function downloadPoster(string $posterPath): ?string
    {
        if (! $posterPath) {
            return null;
        }

        $imageUrl = "{$this->imageBaseUrl}/w500{$posterPath}";
        $basename = basename($posterPath);
        // Organize by first 2 characters: posters/jo/filename.jpg
        // Normalize to lowercase and handle short filenames
        $subdir = strtolower(substr($basename, 0, 2)) ?: '_misc';
        $filename = "posters/{$subdir}/{$basename}";

        try {
            $response = Http::timeout(30)->get($imageUrl);

            if ($response->successful()) {
                // Ensure subdirectory exists
                Storage::disk('public')->makeDirectory("posters/{$subdir}");
                Storage::disk('public')->put($filename, $response->body());

                // Generate optimized sizes and formats
                $posterService = app(\App\Services\PosterImageService::class);
                $posterService->optimizePoster($filename);

                return $filename;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPosterUrl(string $posterPath, string $size = 'w500'): string
    {
        return "{$this->imageBaseUrl}/{$size}{$posterPath}";
    }

    /**
     * Get responsive poster URLs for different contexts.
     *
     * @param  string|null  $posterPath  The TMDB poster path
     * @param  string  $context  The context: 'grid', 'detail', or 'hero'
     * @return array<string, array<string, string>> Returns array with 'webp' and 'jpeg' keys, each containing 'srcset' and 'sizes'
     */
    public function getResponsivePosterUrls(?string $posterPath, string $context = 'grid'): array
    {
        if (! $posterPath) {
            return [
                'webp' => ['srcset' => '', 'sizes' => ''],
                'jpeg' => ['srcset' => '', 'sizes' => ''],
            ];
        }

        $sizes = [
            'grid' => ['w185', 'w342'],
            'detail' => ['w342', 'w500', 'w780'],
            'hero' => ['w500', 'w780', 'original'],
        ];

        $sizeList = $sizes[$context] ?? $sizes['grid'];

        $webpSrcset = [];
        $jpegSrcset = [];

        foreach ($sizeList as $size) {
            $width = $size === 'original' ? '1920' : str_replace('w', '', $size);
            $webpSrcset[] = "{$this->imageBaseUrl}/{$size}{$posterPath}.webp {$width}w";
            $jpegSrcset[] = "{$this->imageBaseUrl}/{$size}{$posterPath} {$width}w";
        }

        $sizesAttr = [
            'grid' => '(max-width: 640px) 33vw, (max-width: 1024px) 25vw, 16vw',
            'detail' => '(max-width: 768px) 100vw, 33vw',
            'hero' => '100vw',
        ];

        return [
            'webp' => [
                'srcset' => implode(', ', $webpSrcset),
                'sizes' => $sizesAttr[$context] ?? $sizesAttr['grid'],
            ],
            'jpeg' => [
                'srcset' => implode(', ', $jpegSrcset),
                'sizes' => $sizesAttr[$context] ?? $sizesAttr['grid'],
            ],
        ];
    }

    public function getYoutubeTrailerUrl(?array $videos): ?string
    {
        if (! $videos || ! isset($videos['results'])) {
            return null;
        }

        foreach ($videos['results'] as $video) {
            if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
                return "https://www.youtube.com/watch?v={$video['key']}";
            }
        }

        return null;
    }
}
