<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use JonesRussell\XSuite\Jobs\PublishXPost;
use JonesRussell\XSuite\Models\XPost;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publish scheduled X posts every minute
Schedule::call(function (): void {
    XPost::query()
        ->readyToPublish()
        ->each(function (XPost $xPost): void {
            PublishXPost::dispatch($xPost);
        });
})->everyMinute()->name('publish-scheduled-x-posts')->withoutOverlapping();

// Sync X analytics every 15 minutes (respect rate limits)
Schedule::command('x-suite:sync-analytics --limit=50')
    ->everyFifteenMinutes()
    ->name('sync-x-analytics')
    ->withoutOverlapping();

// Monitor trends every 30 minutes
Schedule::command('x-suite:monitor-trends')
    ->everyThirtyMinutes()
    ->name('monitor-x-trends')
    ->withoutOverlapping();

// Process auto-replies every 10 minutes
Schedule::command('x-suite:process-auto-replies --limit=10')
    ->everyTenMinutes()
    ->name('process-x-auto-replies')
    ->withoutOverlapping();

// Graduate upcoming movies to released when release date passes (daily at 2 AM)
Schedule::command('movies:graduate-upcoming')
    ->dailyAt('02:00')
    ->name('graduate-upcoming-movies')
    ->withoutOverlapping();

// Discover content daily at 2 AM
Schedule::command('x-suite:discover-content --min-likes=10 --max-results=50')
    ->dailyAt('02:00')
    ->name('discover-x-content')
    ->withoutOverlapping();

// Generate "War Movie of the Day" post daily at 8 AM
Schedule::command('x:generate-content')
    ->dailyAt('08:00')
    ->name('generate-war-movie-post')
    ->withoutOverlapping();

// Import new upcoming war films weekly
Schedule::command('tmdb:import --upcoming --limit=10')
    ->weeklyOn(1, '03:00')
    ->name('tmdb-import-upcoming')
    ->withoutOverlapping();

// Daily import of new war movies (discovery + popular + trending, fill-to-limit)
Schedule::command('tmdb:import --limit=50')
    ->dailyAt('03:30')
    ->name('tmdb-import-daily')
    ->withoutOverlapping();

// Refresh movies that TMDB has updated (changes API)
Schedule::command('tmdb:refresh-changed')
    ->dailyAt('04:30')
    ->name('tmdb-refresh-changed')
    ->withoutOverlapping();

// Refresh stale TMDB data (cadence + max-age) within a window to spread load
Schedule::command('tmdb:refresh-stale --limit=50')
    ->everyFifteenMinutes()
    ->between('03:00', '05:00')
    ->name('tmdb-refresh-stale')
    ->withoutOverlapping();

// Refresh stale TMDB people (lower frequency than movies)
Schedule::command('tmdb:refresh-people --limit=20')
    ->dailyAt('04:00')
    ->name('tmdb-refresh-people')
    ->withoutOverlapping();
