<?php

use App\Jobs\ImportTmdbMovieJob;
use App\Models\Movie;
use App\Services\TmdbMovieImportService;
use Illuminate\Support\Facades\Log;

test('ImportTmdbMovieJob skips and logs when TMDB returns no data', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return str_contains($message, 'skipping')
                && ($context['tmdb_id'] ?? 0) === 99999;
        });

    $this->mock(TmdbMovieImportService::class, function ($mock): void {
        $mock->shouldReceive('import')
            ->once()
            ->with(99999, false)
            ->andReturn(null);
    });

    $job = new ImportTmdbMovieJob(99999);
    $job->handle(app(TmdbMovieImportService::class));
});

test('ImportTmdbMovieJob logs success when movie is imported', function () {
    $movie = Movie::factory()->draft()->create(['tmdb_id' => 12345]);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return str_contains($message, 'imported')
                && ($context['tmdb_id'] ?? 0) === 12345
                && isset($context['movie_id'])
                && ($context['action'] ?? '') === 'updated';
        });

    $this->mock(TmdbMovieImportService::class, function ($mock) use ($movie): void {
        $mock->shouldReceive('import')
            ->once()
            ->with(12345, false)
            ->andReturn(['movie' => $movie, 'action' => 'updated']);
    });

    $job = new ImportTmdbMovieJob(12345);
    $job->handle(app(TmdbMovieImportService::class));
});
