<?php

use App\Data\Tmdb\TmdbMovieData;
use App\Models\Movie;
use App\Services\TmdbMovieImportService;
use App\Services\TMDBService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('TmdbMovieImportService returns null when TMDB returns no data', function () {
    $this->mock(TMDBService::class, function ($mock): void {
        $mock->shouldReceive('getMovieDetailsAsDto')
            ->once()
            ->with(99999)
            ->andReturn(null);
    });

    $service = app(TmdbMovieImportService::class);
    $result = $service->import(99999);

    expect($result)->toBeNull()
        ->and(Movie::where('tmdb_id', 99999)->exists())->toBeFalse();
});

test('TmdbMovieImportService creates movie and syncs tags when TMDB returns data', function () {
    $dtoData = [
        'id' => 88888,
        'title' => 'Test War Movie',
        'overview' => 'A war drama.',
        'release_date' => '2020-06-15',
        'genres' => [['id' => 18, 'name' => 'Drama'], ['id' => 10752, 'name' => 'War']],
        'videos' => ['results' => []],
        'keywords' => ['keywords' => [['id' => 123, 'name' => 'world war ii']]],
        'credits' => [
            'crew' => [['job' => 'Director', 'name' => 'Test Director']],
            'cast' => [],
        ],
    ];
    $dto = TmdbMovieData::fromApiResponse($dtoData);

    $this->mock(TMDBService::class, function ($mock) use ($dto): void {
        $mock->shouldReceive('getMovieDetailsAsDto')
            ->once()
            ->with(88888)
            ->andReturn($dto);
        $mock->shouldReceive('downloadPoster')
            ->andReturn(null);
        $mock->shouldReceive('getPosterUrl')
            ->andReturn('https://image.tmdb.org/t/p/w500/poster.jpg');
    });

    $service = app(TmdbMovieImportService::class);
    $result = $service->import(88888);

    expect($result)->not->toBeNull()
        ->and($result['action'])->toBe('created')
        ->and($result['movie']->title)->toBe('Test War Movie')
        ->and($result['movie']->tmdb_id)->toBe(88888)
        ->and($result['movie']->slug)->toBe('test-war-movie')
        ->and($result['movie']->status->value)->toBe('draft');

    $movie = Movie::where('tmdb_id', 88888)->first();
    expect($movie->tags()->count())->toBeGreaterThan(0);
});
