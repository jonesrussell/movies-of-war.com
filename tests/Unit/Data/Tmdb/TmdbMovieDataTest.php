<?php

use App\Data\Tmdb\TmdbMovieData;

test('it parses director and writers from credits', function () {
    $data = [
        'id' => 190859,
        'title' => 'Jarhead',
        'genres' => [],
        'videos' => ['results' => []],
        'keywords' => ['keywords' => []],
        'credits' => [
            'crew' => [
                ['job' => 'Director', 'name' => 'Sam Mendes'],
                ['job' => 'Writer', 'name' => 'William Broyles Jr.'],
                ['job' => 'Screenplay', 'name' => 'William Broyles Jr.'],
            ],
        ],
    ];

    $dto = TmdbMovieData::fromApiResponse($data);

    expect($dto->director)->toBe('Sam Mendes')
        ->and($dto->writers)->toBe(['William Broyles Jr.']);
});

test('it includes tmdb vote average director and writers in to movie attributes', function () {
    $data = [
        'id' => 190859,
        'title' => 'Jarhead',
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

    $dto = TmdbMovieData::fromApiResponse($data);
    $attributes = $dto->toMovieAttributes();

    expect($attributes['tmdb_vote_average'])->toBe(7.2)
        ->and($attributes['tmdb_vote_count'])->toBe(3500)
        ->and($attributes['director'])->toBe('Sam Mendes')
        ->and($attributes['writers'])->toBe(['William Broyles Jr.']);
});

test('it returns null director and empty writers when credits are absent', function () {
    $data = [
        'id' => 1,
        'title' => 'Test Movie',
        'genres' => [],
        'videos' => ['results' => []],
        'keywords' => ['keywords' => []],
    ];

    $dto = TmdbMovieData::fromApiResponse($data);

    expect($dto->director)->toBeNull()
        ->and($dto->writers)->toBe([]);
});
