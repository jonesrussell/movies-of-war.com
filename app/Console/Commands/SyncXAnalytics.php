<?php

namespace App\Console\Commands;

use App\Services\XAnalyticsService;
use Illuminate\Console\Command;

class SyncXAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x:sync-analytics {--limit=50 : Maximum number of posts to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync analytics metrics for all published X posts';

    /**
     * Execute the console command.
     */
    public function handle(XAnalyticsService $analyticsService): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Syncing analytics for up to {$limit} published posts...");

        $synced = $analyticsService->syncPublishedPosts($limit);

        $this->info("Successfully synced metrics for {$synced} posts.");

        return Command::SUCCESS;
    }
}
