<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Tmdb\TmdbDiscoverResponse;
use App\Data\Tmdb\TmdbMovieData;
use App\Data\Tmdb\TmdbSearchResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TMDBService
{
    protected string $apiKey;

    protected string $baseUrl;

    protected string $imageBaseUrl;

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
