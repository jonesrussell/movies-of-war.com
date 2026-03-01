<?php

use App\Data\Tmdb\TmdbMovieData;
use App\Jobs\RefreshTmdbMovieJob;
use App\Models\Movie;
use App\Services\PersonSyncService;
use App\Services\TMDBService;
use Illuminate\Support\Facades\Log;

test('RefreshTmdbMovieJob updates movie from DTO and sets tmdb_last_synced_at', function () {
    $movie = Movie::factory()->create([
        'tmdb_id' => 190859,
        'tmdb_vote_average' => null,
        'tmdb_vote_count' => null,
        'director' => null,
        'writers' => null,
        'tmdb_last_synced_at' => null,
    ]);

    $dtoData = [
        'id' => 190859,
        'title' => 'Jarhead',
        'overview' => 'A drama about a soldier.',
        'release_date' => '2005-11-04',
        'genres' => [],
        'videos' => ['results' => []],
        'keywords' => ['keywords' => []],
        'vote_average' => 7.2,
        'vote_count' => 3500,
        'credits' => [
            'crew' => [
                ['job' => 'Director', 'name' => 'Sam Mendes'],
                ['job' => 'Writer', 'name' => 'William Broyles Jr.'],
            ],
        ],
    ];
    $dto = TmdbMovieData::fromApiResponse($dtoData);

    $this->mock(TMDBService::class, function ($mock) use ($dto): void {
        $mock->shouldReceive('getMovieDetailsAsDto')
            ->once()
            ->with(190859)
            ->andReturn($dto);
    });

    $job = new RefreshTmdbMovieJob($movie);
    $job->handle(app(TMDBService::class), app(PersonSyncService::class));

    $movie->refresh();
    expect($movie->tmdb_vote_average)->toBe(7.2)
        ->and($movie->tmdb_vote_count)->toBe(3500)
        ->and($movie->director)->toBe('Sam Mendes')
        ->and($movie->writers)->toBe(['William Broyles Jr.'])
        ->and($movie->tmdb_last_synced_at)->not->toBeNull();
});

test('RefreshTmdbMovieJob does not overwrite slug poster_path or poster_url', function () {
    $movie = Movie::factory()->create([
        'tmdb_id' => 1,
        'slug' => 'custom-slug',
        'poster_path' => 'posters/ab/abc.jpg',
        'poster_url' => 'https://example.com/poster.jpg',
        'tmdb_last_synced_at' => null,
    ]);

    $dtoData = [
        'id' => 1,
        'title' => 'New Title',
        'overview' => 'Overview',
        'release_date' => '2020-01-01',
        'genres' => [],
        'videos' => ['results' => []],
        'keywords' => ['keywords' => []],
        'credits' => ['crew' => []],
    ];
    $dto = TmdbMovieData::fromApiResponse($dtoData);

    $this->mock(TMDBService::class, function ($mock) use ($dto): void {
        $mock->shouldReceive('getMovieDetailsAsDto')
            ->once()
            ->andReturn($dto);
    });

    $job = new RefreshTmdbMovieJob($movie);
    $job->handle(app(TMDBService::class), app(PersonSyncService::class));

    $movie->refresh();
    expect($movie->slug)->toBe('custom-slug')
        ->and($movie->poster_path)->toBe('posters/ab/abc.jpg')
        ->and($movie->poster_url)->toBe('https://example.com/poster.jpg');
});

test('RefreshTmdbMovieJob preserves release_year when TMDB returns null release_date', function () {
    $movie = Movie::factory()->create([
        'tmdb_id' => 35,
        'release_year' => 2026,
        'release_date' => '2026-01-01',
        'tmdb_last_synced_at' => null,
    ]);

    $dtoData = [
        'id' => 35,
        'title' => 'Some Post-Production Film',
        'overview' => 'A film still in production.',
        'release_date' => '',
        'genres' => [],
        'videos' => ['results' => []],
        'keywords' => ['keywords' => []],
        'credits' => [
            'crew' => [
                ['job' => 'Director', 'name' => 'Shane Stanley'],
                ['job' => 'Writer', 'name' => 'Lee Wilson'],
            ],
        ],
        'status' => 'Post Production',
    ];
    $dto = TmdbMovieData::fromApiResponse($dtoData);

    $this->mock(TMDBService::class, function ($mock) use ($dto): void {
        $mock->shouldReceive('getMovieDetailsAsDto')
            ->once()
            ->with(35)
            ->andReturn($dto);
    });

    $job = new RefreshTmdbMovieJob($movie);
    $job->handle(app(TMDBService::class), app(PersonSyncService::class));

    $movie->refresh();
    expect($movie->release_year)->toBe(2026)
        ->and($movie->release_date->toDateString())->toBe('2026-01-01')
        ->and($movie->director)->toBe('Shane Stanley')
        ->and($movie->production_status)->toBe('Post Production')
        ->and($movie->tmdb_last_synced_at)->not->toBeNull();
});

test('RefreshTmdbMovieJob does nothing when TMDB returns null', function () {
    Log::shouldReceive('warning')->once();

    $movie = Movie::factory()->create([
        'tmdb_id' => 999,
        'tmdb_vote_average' => 5.0,
        'tmdb_last_synced_at' => null,
    ]);

    $this->mock(TMDBService::class, function ($mock): void {
        $mock->shouldReceive('getMovieDetailsAsDto')
            ->once()
            ->with(999)
            ->andReturn(null);
    });

    $job = new RefreshTmdbMovieJob($movie);
    $job->handle(app(TMDBService::class), app(PersonSyncService::class));

    $movie->refresh();
    expect($movie->tmdb_last_synced_at)->toBeNull()
        ->and((float) $movie->tmdb_vote_average)->toBe(5.0);
});
