<?php

namespace App\Console\Commands;

use App\Services\XContentDiscoveryService;
use Illuminate\Console\Command;

class DiscoverXContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x:discover-content {--min-likes=10 : Minimum likes to include} {--max-results=50 : Maximum results to discover}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and curate high-quality war movie content from X';

    /**
     * Execute the console command.
     */
    public function handle(XContentDiscoveryService $discoveryService): int
    {
        $minLikes = (int) $this->option('min-likes');
        $maxResults = (int) $this->option('max-results');

        $this->info("Discovering content with minimum {$minLikes} likes...");

        $filters = [
            'min_likes' => $minLikes,
            'max_results' => $maxResults,
        ];

        try {
            $discovered = $discoveryService->discoverContent($filters);

            $this->info("Discovered {$discovered} new curated posts.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to discover content: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
