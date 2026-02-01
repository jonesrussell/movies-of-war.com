<?php

use App\Jobs\PublishXPost;
use App\Models\XPost;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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
Schedule::command('x:sync-analytics --limit=50')
    ->everyFifteenMinutes()
    ->name('sync-x-analytics')
    ->withoutOverlapping();

// Monitor trends every 30 minutes
Schedule::command('x:monitor-trends')
    ->everyThirtyMinutes()
    ->name('monitor-x-trends')
    ->withoutOverlapping();

// Process auto-replies every 10 minutes
Schedule::command('x:process-auto-replies --limit=10')
    ->everyTenMinutes()
    ->name('process-x-auto-replies')
    ->withoutOverlapping();

// Discover content daily at 2 AM
Schedule::command('x:discover-content --min-likes=10 --max-results=50')
    ->dailyAt('02:00')
    ->name('discover-x-content')
    ->withoutOverlapping();

// Generate "War Movie of the Day" post daily at 8 AM
Schedule::command('x:generate-content')
    ->dailyAt('08:00')
    ->name('generate-war-movie-post')
    ->withoutOverlapping();

// Refresh stale TMDB data (cadence + max-age) within a window to spread load
Schedule::command('tmdb:refresh-stale --limit=50')
    ->everyFifteenMinutes()
    ->between('03:00', '05:00')
    ->name('tmdb-refresh-stale')
    ->withoutOverlapping();
