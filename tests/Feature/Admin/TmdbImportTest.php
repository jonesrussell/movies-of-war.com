<?php

use App\Jobs\ImportTmdbMoviesJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('admin users can queue an upcoming-only TMDB import', function () {
    Queue::fake();

    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->post(route('tmdb.import'), [
        'limit' => 10,
        'upcoming' => true,
    ]);

    $response->assertRedirect(route('dashboard.tmdb-imports'));

    Queue::assertPushed(ImportTmdbMoviesJob::class, function (ImportTmdbMoviesJob $job) {
        return $job->limit === 10
            && $job->upcoming === true;
    });
});

test('non-admin users cannot queue a TMDB import', function () {
    Queue::fake();

    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->post(route('tmdb.import'), [
        'limit' => 10,
        'upcoming' => true,
    ]);

    $response->assertForbidden();

    Queue::assertNothingPushed();
});
