<?php

use App\Data\Tmdb\TmdbDiscoverResponse;
use App\Jobs\ImportTmdbMovieJob;
use App\Services\TMDBService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Config::set('tmdb.api_key', 'test-key');
    Config::set('tmdb.import.discovery_strategies', [
        [
            'key' => 'war_genre',
            'type' => 'genre',
            'genre_id' => 10752,
        ],
    ]);
    Config::set('tmdb.import.min_vote_count', 10);
    Config::set('tmdb.import.gap_filling_enabled', false);
});

test('tmdb import command dispatches ImportTmdbMovieJob for each candidate when not sync', function () {
    Queue::fake();

    $this->mock(TMDBService::class, function ($mock): void {
        $mock->shouldReceive('discoverMoviesAsDto')
            ->andReturn(new TmdbDiscoverResponse(
                collect([
                    ['id' => 100, 'title' => 'Movie A', 'overview' => 'War film', 'vote_count' => 500, 'vote_average' => 7.5],
                    ['id' => 101, 'title' => 'Movie B', 'overview' => 'Battle', 'vote_count' => 200, 'vote_average' => 7.0],
                ]),
                1,
                2,
                1
            ));
    });

    $this->artisan('tmdb:import', ['--limit' => 2])
        ->assertSuccessful();

    Queue::assertPushed(ImportTmdbMovieJob::class, 2);
    Queue::assertPushed(ImportTmdbMovieJob::class, function (ImportTmdbMovieJob $job) {
        return in_array($job->tmdbId, [100, 101], true);
    });
});

test('tmdb import command with sync does not dispatch jobs', function () {
    Queue::fake();

    $this->mock(TMDBService::class, function ($mock): void {
        $mock->shouldReceive('discoverMoviesAsDto')
            ->andReturn(new TmdbDiscoverResponse(collect([]), 1, 0, 1));
    });

    $this->artisan('tmdb:import', ['--limit' => 1, '--sync' => true])
        ->assertSuccessful();

    Queue::assertNothingPushed();
});
