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

    $response->assertRedirect(route('dashboard.tmdb.imports'));

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

test('admin users can import a single movie from TMDB search via service', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->mock(\App\Services\TmdbMovieImportService::class, function ($mock): void {
        $movie = \App\Models\Movie::factory()->draft()->create(['title' => 'Imported Movie']);
        $mock->shouldReceive('import')
            ->once()
            ->with(12345)
            ->andReturn(['movie' => $movie, 'action' => 'created']);
    });

    $response = $this->actingAs($user)->post(route('tmdb.import-single'), [
        'tmdb_id' => 12345,
    ]);

    $response->assertRedirect(route('dashboard.tmdb.search'))
        ->assertSessionHas('success');
});

test('admin users see error when single movie import returns no TMDB data', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->mock(\App\Services\TmdbMovieImportService::class, function ($mock): void {
        $mock->shouldReceive('import')
            ->once()
            ->with(99999)
            ->andReturn(null);
    });

    $response = $this->actingAs($user)->post(route('tmdb.import-single'), [
        'tmdb_id' => 99999,
    ]);

    $response->assertRedirect(route('dashboard.tmdb.search'))
        ->assertSessionHas('error');
});
