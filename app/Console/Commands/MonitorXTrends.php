<?php

namespace App\Console\Commands;

use App\Services\XTrendMonitoringService;
use Illuminate\Console\Command;

class MonitorXTrends extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x:monitor-trends';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search all active trend keywords and store results';

    /**
     * Execute the console command.
     */
    public function handle(XTrendMonitoringService $trendService): int
    {
        $this->info('Monitoring active trend keywords...');

        $results = $trendService->monitorAllActiveKeywords();

        $this->info("Found {$results} new trending tweets.");

        return Command::SUCCESS;
    }
}
