<?php

use App\Data\Tmdb\TmdbDiscoverResponse;
use App\Services\TMDBService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('tmdb.api_key', 'test-key');
    Config::set('tmdb.base_url', 'https://api.themoviedb.org/3');
});

test('getPopularMoviesAsDto returns TmdbDiscoverResponse from API', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/popular*' => Http::response([
            'page' => 1,
            'results' => [
                ['id' => 1, 'title' => 'Popular A', 'vote_count' => 100, 'vote_average' => 7.0],
                ['id' => 2, 'title' => 'Popular B', 'vote_count' => 200, 'vote_average' => 8.0],
            ],
            'total_pages' => 1,
            'total_results' => 2,
        ], 200),
    ]);

    $service = app(TMDBService::class);
    $dto = $service->getPopularMoviesAsDto(1);

    expect($dto)->toBeInstanceOf(TmdbDiscoverResponse::class)
        ->and($dto->results->count())->toBe(2)
        ->and($dto->results->pluck('id')->all())->toEqual([1, 2]);
});

test('getTrendingMoviesAsDto returns TmdbDiscoverResponse from API', function () {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day*' => Http::response([
            'page' => 1,
            'results' => [
                ['id' => 10, 'title' => 'Trending A', 'vote_count' => 50],
            ],
            'total_pages' => 1,
            'total_results' => 1,
        ], 200),
    ]);

    $service = app(TMDBService::class);
    $dto = $service->getTrendingMoviesAsDto(1, 'day');

    expect($dto)->toBeInstanceOf(TmdbDiscoverResponse::class)
        ->and($dto->results->count())->toBe(1)
        ->and($dto->results->first()['id'])->toBe(10);
});

test('getMovieChangeIds returns ids from changes API and paginates', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/changes*page=1*' => Http::response([
            'results' => [['id' => 100], ['id' => 101]],
            'page' => 1,
            'total_pages' => 2,
        ], 200),
        'api.themoviedb.org/3/movie/changes*page=2*' => Http::response([
            'results' => [['id' => 102]],
            'page' => 2,
            'total_pages' => 2,
        ], 200),
    ]);

    $service = app(TMDBService::class);
    $ids = $service->getMovieChangeIds('2025-01-01', '2025-01-02');

    expect($ids)->toEqual([100, 101, 102]);
});
