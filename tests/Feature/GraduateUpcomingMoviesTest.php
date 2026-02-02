<?php

use App\Jobs\RefreshTmdbMovieJob;
use App\Models\Movie;
use Illuminate\Support\Facades\Queue;

test('movies:graduate-upcoming with no candidates outputs message and does nothing', function () {
    Queue::fake();

    Movie::factory()->create([
        'is_upcoming' => false,
        'release_date' => now()->subDay(),
    ]);

    $this->artisan('movies:graduate-upcoming')
        ->assertSuccessful()
        ->expectsOutput('No upcoming movies to graduate.');

    Queue::assertNothingPushed();
});

test('movies:graduate-upcoming --dry-run lists candidates without making changes', function () {
    Queue::fake();

    $movie = Movie::factory()->upcoming()->create([
        'release_date' => now()->subDay(),
        'tmdb_id' => 12345,
    ]);

    $this->artisan('movies:graduate-upcoming', ['--dry-run' => true])
        ->assertSuccessful();

    Queue::assertNothingPushed();
    expect($movie->fresh()->is_upcoming)->toBeTrue();
});

test('movies:graduate-upcoming dispatches RefreshTmdbMovieJob for movies with tmdb_id', function () {
    Queue::fake();

    $movie = Movie::factory()->upcoming()->create([
        'release_date' => now()->subDay(),
        'tmdb_id' => 12345,
    ]);

    $this->artisan('movies:graduate-upcoming')
        ->assertSuccessful();

    Queue::assertPushed(RefreshTmdbMovieJob::class, function (RefreshTmdbMovieJob $job) use ($movie) {
        return $job->movie->id === $movie->id;
    });
});

test('movies:graduate-upcoming directly updates is_upcoming for movies without tmdb_id', function () {
    Queue::fake();

    $movie = Movie::factory()->upcoming()->create([
        'release_date' => now()->subDay(),
        'tmdb_id' => null,
    ]);

    $this->artisan('movies:graduate-upcoming')
        ->assertSuccessful();

    Queue::assertNothingPushed();
    expect($movie->fresh()->is_upcoming)->toBeFalse();
});

test('movies:graduate-upcoming does not touch upcoming movies with future release date', function () {
    Queue::fake();

    $movie = Movie::factory()->upcoming()->create([
        'release_date' => now()->addDay(),
        'tmdb_id' => 12345,
    ]);

    $this->artisan('movies:graduate-upcoming')
        ->assertSuccessful()
        ->expectsOutput('No upcoming movies to graduate.');

    Queue::assertNothingPushed();
    expect($movie->fresh()->is_upcoming)->toBeTrue();
});
