<?php

declare(strict_types=1);

use App\Data\Tmdb\TmdbMovieData;
use App\Models\Movie;
use App\Services\PersonSyncService;

test('PersonSyncService does not create duplicate pivot rows when syncing same movie twice', function () {
    $movie = Movie::factory()->create(['tmdb_id' => 123]);

    $dtoData = [
        'id' => 123,
        'title' => 'Test Movie',
        'overview' => 'Overview',
        'release_date' => '2020-01-01',
        'genres' => [],
        'videos' => ['results' => []],
        'keywords' => ['keywords' => []],
        'credits' => [
            'cast' => [
                ['id' => 1, 'name' => 'Actor One', 'character' => 'Hero', 'order' => 0, 'profile_path' => null],
            ],
            'crew' => [
                ['id' => 2, 'name' => 'Director One', 'job' => 'Director', 'department' => 'Directing', 'profile_path' => null],
            ],
        ],
    ];
    $dto = TmdbMovieData::fromApiResponse($dtoData);

    $service = app(PersonSyncService::class);

    $service->syncFromMovieDto($movie, $dto);
    $countAfterFirst = $movie->people()->count();

    $service->syncFromMovieDto($movie, $dto);
    $countAfterSecond = $movie->people()->count();

    expect($countAfterFirst)->toBe($countAfterSecond)
        ->and($countAfterFirst)->toBeGreaterThan(0);
});
