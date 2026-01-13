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
