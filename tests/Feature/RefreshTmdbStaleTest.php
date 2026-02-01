<?php

use App\Jobs\RefreshTmdbMovieJob;
use App\Models\Movie;
use Illuminate\Support\Facades\Queue;

test('tmdb:refresh-stale with no candidates outputs message and dispatches nothing', function () {
    Queue::fake();

    Movie::factory()->create(['tmdb_id' => null]);

    $this->artisan('tmdb:refresh-stale')
        ->assertSuccessful()
        ->expectsOutput('No movies to refresh.');

    Queue::assertNothingPushed();
});

test('tmdb:refresh-stale --dry-run lists candidates and dispatches no jobs', function () {
    Queue::fake();

    $movie = Movie::factory()->create([
        'tmdb_id' => 12345,
        'tmdb_last_synced_at' => null,
    ]);

    $this->artisan('tmdb:refresh-stale', ['--dry-run' => true])
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

test('tmdb:refresh-stale --force-backfill dispatches jobs only for never-synced movies', function () {
    Queue::fake();

    $synced = Movie::factory()->create([
        'tmdb_id' => 1,
        'tmdb_last_synced_at' => now(),
    ]);
    $neverSynced = Movie::factory()->create([
        'tmdb_id' => 2,
        'tmdb_last_synced_at' => null,
    ]);

    $this->artisan('tmdb:refresh-stale', ['--force-backfill' => true, '--limit' => 10])
        ->assertSuccessful();

    Queue::assertPushed(RefreshTmdbMovieJob::class, 1);
    Queue::assertPushed(RefreshTmdbMovieJob::class, function (RefreshTmdbMovieJob $job) use ($neverSynced) {
        return $job->movie->id === $neverSynced->id;
    });
});

test('tmdb:refresh-stale --force dispatches jobs for all movies with tmdb_id up to limit', function () {
    Queue::fake();

    Movie::factory()->create(['tmdb_id' => 1, 'tmdb_last_synced_at' => now()]);
    Movie::factory()->create(['tmdb_id' => 2, 'tmdb_last_synced_at' => now()]);
    Movie::factory()->create(['tmdb_id' => 3, 'tmdb_last_synced_at' => now()]);

    $this->artisan('tmdb:refresh-stale', ['--force' => true, '--limit' => 2])
        ->assertSuccessful();

    Queue::assertPushed(RefreshTmdbMovieJob::class, 2);
});

test('tmdb:refresh-stale dispatches jobs for stale movies by cadence', function () {
    Queue::fake();

    $stalePopular = Movie::factory()->create([
        'tmdb_id' => 1,
        'tmdb_vote_count' => 15_000,
        'tmdb_last_synced_at' => now()->subDays(10),
    ]);
    $freshPopular = Movie::factory()->create([
        'tmdb_id' => 2,
        'tmdb_vote_count' => 15_000,
        'tmdb_last_synced_at' => now()->subDays(3),
    ]);

    $this->artisan('tmdb:refresh-stale', ['--limit' => 10])
        ->assertSuccessful();

    Queue::assertPushed(RefreshTmdbMovieJob::class, 1);
    Queue::assertPushed(RefreshTmdbMovieJob::class, function (RefreshTmdbMovieJob $job) use ($stalePopular) {
        return $job->movie->id === $stalePopular->id;
    });
});
