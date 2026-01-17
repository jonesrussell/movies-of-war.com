<?php

namespace App\Console\Commands;

use App\Services\XAutoReplyService;
use Illuminate\Console\Command;

class ProcessXAutoReplies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x:process-auto-replies {--limit=10 : Maximum mentions to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process mentions and send auto-replies based on rules';

    /**
     * Execute the console command.
     */
    public function handle(XAutoReplyService $autoReplyService): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Processing up to {$limit} mentions...");

        $repliesSent = $autoReplyService->processMentions($limit);

        $this->info("Sent {$repliesSent} auto-replies.");

        return Command::SUCCESS;
    }
}
