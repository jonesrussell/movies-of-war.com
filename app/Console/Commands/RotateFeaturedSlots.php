<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SlotType;
use App\Models\FeaturedSlot;
use App\Models\FeaturedSlotHistory;
use App\Models\FeaturedSlotQueue;
use App\Services\FeaturedSlotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RotateFeaturedSlots extends Command
{
    protected $signature = 'featured:rotate
                            {--dry-run : Show what would happen without making changes}';

    protected $description = 'Rotate featured slots: archive current, swap in next from queue, refill queue';

    public function handle(FeaturedSlotService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Rotating featured slots...');

        $slots = [SlotType::Hero, SlotType::PickOfWeek];
        $failed = false;

        foreach ($slots as $slotType) {
            $next = FeaturedSlotQueue::slot($slotType)->nextUp()->first();

            if (! $next) {
                $this->line("  No queue entry for {$slotType->value}, auto-selecting...");
                if (! $dryRun) {
                    $service->fillQueue();
                    $next = FeaturedSlotQueue::slot($slotType)->nextUp()->first();
                }
            }

            if (! $next) {
                $this->warn("  No eligible movie for {$slotType->value}. Keeping current.");

                continue;
            }

            $next->load('movie');
            $movieTitle = $next->movie?->title ?? 'Unknown';

            if ($dryRun) {
                $this->line("  [DRY RUN] {$slotType->value}: would swap to \"{$movieTitle}\"");

                continue;
            }

            try {
                DB::transaction(function () use ($slotType, $next, $service, $movieTitle): void {
                    // Archive current
                    FeaturedSlotHistory::current()->slot($slotType)->update([
                        'ended_at' => now(),
                    ]);

                    // Swap in
                    FeaturedSlot::where('slot', $slotType)->delete();
                    FeaturedSlot::create([
                        'movie_id' => $next->movie_id,
                        'slot' => $slotType,
                    ]);

                    // Log to history
                    FeaturedSlotHistory::create([
                        'movie_id' => $next->movie_id,
                        'slot' => $slotType,
                        'selection_method' => $next->selection_method,
                        'started_at' => now(),
                    ]);

                    // Remove consumed entry
                    $next->delete();

                    // Reindex remaining positions for this slot
                    $service->reindexPositions($slotType);

                    Log::info("Featured slot rotated: {$slotType->value}", [
                        'movie_id' => $next->movie_id,
                        'movie_title' => $movieTitle,
                        'selection_method' => $next->selection_method->value,
                    ]);
                });

                $this->line("  {$slotType->value}: swapped to \"{$movieTitle}\"");
            } catch (\Throwable $e) {
                $failed = true;
                Log::error("Featured slot rotation failed for {$slotType->value}", [
                    'movie_id' => $next->movie_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  {$slotType->value}: rotation FAILED - {$e->getMessage()}");
            }
        }

        // Refill queue
        if (! $dryRun) {
            $service->fillQueue();
            $this->info('Queue refilled.');
        }

        $this->info('Rotation complete.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
