<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Tmdb\TmdbMovieData;
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

    public function getMovieDetails(int $tmdbId): ?array
    {
        $response = Http::get("{$this->baseUrl}/movie/{$tmdbId}", [
            'api_key' => $this->apiKey,
            'append_to_response' => 'videos,keywords',
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

        return [
            'results' => $data['results'] ?? [],
            'total_pages' => $data['total_pages'] ?? 1,
            'total_results' => $data['total_results'] ?? 0,
        ];
    }

    public function downloadPoster(string $posterPath): ?string
    {
        if (! $posterPath) {
            return null;
        }

        $imageUrl = "{$this->imageBaseUrl}/w500{$posterPath}";
        $filename = 'posters/'.basename($posterPath);

        try {
            $response = Http::timeout(30)->get($imageUrl);

            if ($response->successful()) {
                Storage::disk('public')->put($filename, $response->body());

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
