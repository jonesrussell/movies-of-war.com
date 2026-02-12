<?php

use App\Jobs\RefreshTmdbMovieJob;
use App\Models\Movie;
use App\Services\TMDBService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

test('tmdb:refresh-changed with no API key fails', function () {
    Config::set('tmdb.api_key', null);

    $this->artisan('tmdb:refresh-changed')
        ->assertFailed();
});

test('tmdb:refresh-changed when TMDB returns no changed ids outputs message and dispatches nothing', function () {
    Queue::fake();
    Config::set('tmdb.api_key', 'test-key');

    $this->mock(TMDBService::class, function ($mock): void {
        $mock->shouldReceive('getMovieChangeIds')
            ->once()
            ->andReturn([]);
    });

    $this->artisan('tmdb:refresh-changed')
        ->assertSuccessful()
        ->expectsOutput('No changed movies reported by TMDB.');

    Queue::assertNothingPushed();
});

test('tmdb:refresh-changed dispatches RefreshTmdbMovieJob only for movies we have that changed on TMDB', function () {
    Queue::fake();
    Config::set('tmdb.api_key', 'test-key');

    $movieWeHave = Movie::factory()->create(['tmdb_id' => 500]);
    Movie::factory()->create(['tmdb_id' => 999]); // we have it but TMDB won't report it in this batch

    $this->mock(TMDBService::class, function ($mock): void {
        $mock->shouldReceive('getMovieChangeIds')
            ->once()
            ->andReturn([500, 600]); // 500 we have, 600 we don't
    });

    $this->artisan('tmdb:refresh-changed')
        ->assertSuccessful();

    Queue::assertPushed(RefreshTmdbMovieJob::class, 1);
    Queue::assertPushed(RefreshTmdbMovieJob::class, function (RefreshTmdbMovieJob $job) use ($movieWeHave) {
        return $job->movie->id === $movieWeHave->id;
    });
});

test('tmdb:refresh-changed respects --limit', function () {
    Queue::fake();
    Config::set('tmdb.api_key', 'test-key');

    Movie::factory()->create(['tmdb_id' => 1]);
    Movie::factory()->create(['tmdb_id' => 2]);
    Movie::factory()->create(['tmdb_id' => 3]);

    $this->mock(TMDBService::class, function ($mock): void {
        $mock->shouldReceive('getMovieChangeIds')
            ->once()
            ->andReturn([1, 2, 3]);
    });

    $this->artisan('tmdb:refresh-changed', ['--limit' => 2])
        ->assertSuccessful();

    Queue::assertPushed(RefreshTmdbMovieJob::class, 2);
});
