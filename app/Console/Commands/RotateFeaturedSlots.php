<?php

declare(strict_types=1);

namespace App\Console\Commands;

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

        $slots = ['hero', 'pick_of_week'];

        foreach ($slots as $slotType) {
            $next = FeaturedSlotQueue::slot($slotType)->nextUp()->first();

            if (! $next) {
                $this->line("  No queue entry for {$slotType}, auto-selecting...");
                if (! $dryRun) {
                    $service->fillQueue();
                    $next = FeaturedSlotQueue::slot($slotType)->nextUp()->first();
                }
            }

            if (! $next) {
                $this->warn("  No eligible movie for {$slotType}. Keeping current.");

                continue;
            }

            $next->load('movie');
            $movieTitle = $next->movie?->title ?? 'Unknown';

            if ($dryRun) {
                $this->line("  [DRY RUN] {$slotType}: would swap to \"{$movieTitle}\"");

                continue;
            }

            DB::transaction(function () use ($slotType, $next): void {
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
                FeaturedSlotQueue::slot($slotType)
                    ->orderBy('position')
                    ->get()
                    ->each(function (FeaturedSlotQueue $entry, int $index): void {
                        $entry->update(['position' => $index + 1]);
                    });
            });

            $this->line("  {$slotType}: swapped to \"{$movieTitle}\"");
            Log::info("Featured slot rotated: {$slotType}", [
                'movie_id' => $next->movie_id,
                'selection_method' => $next->selection_method,
            ]);
        }

        // Refill queue
        if (! $dryRun) {
            $service->fillQueue();
            $this->info('Queue refilled.');
        }

        $this->info('Rotation complete.');

        return self::SUCCESS;
    }
}
