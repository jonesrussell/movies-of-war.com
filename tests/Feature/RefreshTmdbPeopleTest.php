<?php

declare(strict_types=1);

use App\Jobs\RefreshTmdbPersonJob;
use App\Models\Person;
use Illuminate\Support\Facades\Queue;

test('tmdb:refresh-people with no candidates outputs message and dispatches nothing', function () {
    Queue::fake();

    $this->artisan('tmdb:refresh-people')
        ->assertSuccessful()
        ->expectsOutput('No people to refresh.');

    Queue::assertNothingPushed();
});

test('tmdb:refresh-people --dry-run lists candidates and dispatches no jobs', function () {
    Queue::fake();

    Person::factory()->create([
        'tmdb_id' => 12345,
        'tmdb_last_synced_at' => null,
    ]);

    $this->artisan('tmdb:refresh-people', ['--dry-run' => true])
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

test('tmdb:refresh-people --force-backfill dispatches jobs only for never-synced people', function () {
    Queue::fake();

    $synced = Person::factory()->create([
        'tmdb_id' => 1,
        'tmdb_last_synced_at' => now(),
    ]);
    $neverSynced = Person::factory()->create([
        'tmdb_id' => 2,
        'tmdb_last_synced_at' => null,
    ]);

    $this->artisan('tmdb:refresh-people', ['--force-backfill' => true, '--limit' => 10])
        ->assertSuccessful();

    Queue::assertPushed(RefreshTmdbPersonJob::class, 1);
    Queue::assertPushed(RefreshTmdbPersonJob::class, function (RefreshTmdbPersonJob $job) use ($neverSynced) {
        return $job->person->id === $neverSynced->id;
    });
});
