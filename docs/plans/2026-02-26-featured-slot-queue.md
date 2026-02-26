# Featured Slot Queue & Auto-Publish Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Automated weekly rotation of featured slots with a previewable queue, admin overrides, and historical tracking.

**Architecture:** Two new tables (`featured_slot_queue`, `featured_slot_history`) alongside the existing `featured_slots` table. A `FeaturedSlotService` handles selection logic and rotation. A scheduled `featured:rotate` command runs every Sunday at 6:00 AM UTC. Admin pages for queue preview/override and history browsing.

**Tech Stack:** Laravel 12, Pest 4, Inertia v2 + Vue 3, Tailwind CSS 4

**Design doc:** `docs/plans/2026-02-26-featured-slot-queue-design.md`

---

### Task 1: Create `featured_slot_history` migration and model

**Files:**
- Create: `database/migrations/2026_02_26_000001_create_featured_slot_history_table.php`
- Create: `app/Models/FeaturedSlotHistory.php`
- Create: `database/factories/FeaturedSlotHistoryFactory.php`

**Step 1: Create the migration**

Run: `ddev artisan make:migration create_featured_slot_history_table --no-interaction`

Then replace the generated file contents:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_slot_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slot')->comment('hero, pick_of_week');
            $table->string('selection_method')->comment('auto, manual');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['slot', 'started_at']);
            $table->index('movie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_slot_history');
    }
};
```

**Step 2: Create the model**

Run: `ddev artisan make:model FeaturedSlotHistory --no-interaction`

Then replace contents:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedSlotHistory extends Model
{
    /** @use HasFactory<\Database\Factories\FeaturedSlotHistoryFactory> */
    use HasFactory;

    protected $table = 'featured_slot_history';

    protected $fillable = [
        'movie_id',
        'slot',
        'selection_method',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * @param  Builder<FeaturedSlotHistory>  $query
     * @return Builder<FeaturedSlotHistory>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * @param  Builder<FeaturedSlotHistory>  $query
     * @return Builder<FeaturedSlotHistory>
     */
    public function scopeSlot(Builder $query, string $slot): Builder
    {
        return $query->where('slot', $slot);
    }
}
```

**Step 3: Create the factory**

Run: `ddev artisan make:factory FeaturedSlotHistoryFactory --no-interaction`

Then replace contents:

```php
<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeaturedSlotHistory>
 */
class FeaturedSlotHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'slot' => fake()->randomElement(['hero', 'pick_of_week']),
            'selection_method' => 'auto',
            'started_at' => now()->subWeek(),
            'ended_at' => now(),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now(),
            'ended_at' => null,
        ]);
    }
}
```

**Step 4: Run migration**

Run: `ddev artisan migrate`
Expected: Migration runs successfully.

**Step 5: Commit**

```bash
git add -A && git commit -m "feat: add featured_slot_history table and model"
```

---

### Task 2: Create `featured_slot_queue` migration and model

**Files:**
- Create: `database/migrations/2026_02_26_000002_create_featured_slot_queue_table.php`
- Create: `app/Models/FeaturedSlotQueue.php`
- Create: `database/factories/FeaturedSlotQueueFactory.php`

**Step 1: Create the migration**

Run: `ddev artisan make:migration create_featured_slot_queue_table --no-interaction`

Then replace:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_slot_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->string('slot')->comment('hero, pick_of_week');
            $table->unsignedInteger('position');
            $table->string('selection_method')->default('auto')->comment('auto, manual');
            $table->date('scheduled_for');
            $table->timestamps();

            $table->unique(['slot', 'position']);
            $table->index('movie_id');
            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_slot_queue');
    }
};
```

**Step 2: Create the model**

Run: `ddev artisan make:model FeaturedSlotQueue --no-interaction`

Then replace:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedSlotQueue extends Model
{
    /** @use HasFactory<\Database\Factories\FeaturedSlotQueueFactory> */
    use HasFactory;

    protected $table = 'featured_slot_queue';

    protected $fillable = [
        'movie_id',
        'slot',
        'position',
        'selection_method',
        'scheduled_for',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'scheduled_for' => 'date',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * @param  Builder<FeaturedSlotQueue>  $query
     * @return Builder<FeaturedSlotQueue>
     */
    public function scopeSlot(Builder $query, string $slot): Builder
    {
        return $query->where('slot', $slot);
    }

    /**
     * @param  Builder<FeaturedSlotQueue>  $query
     * @return Builder<FeaturedSlotQueue>
     */
    public function scopeNextUp(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
```

**Step 3: Create the factory**

Run: `ddev artisan make:factory FeaturedSlotQueueFactory --no-interaction`

Then replace:

```php
<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeaturedSlotQueue>
 */
class FeaturedSlotQueueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'slot' => fake()->randomElement(['hero', 'pick_of_week']),
            'position' => 1,
            'selection_method' => 'auto',
            'scheduled_for' => now()->next('Sunday'),
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'selection_method' => 'manual',
        ]);
    }
}
```

**Step 4: Run migration**

Run: `ddev artisan migrate`
Expected: Migration runs successfully.

**Step 5: Commit**

```bash
git add -A && git commit -m "feat: add featured_slot_queue table and model"
```

---

### Task 3: Create `FeaturedSlotService` — selection algorithm

**Files:**
- Create: `app/Services/FeaturedSlotService.php`
- Create: `tests/Feature/FeaturedSlotServiceTest.php`

**Step 1: Write the failing tests**

Run: `ddev artisan make:test FeaturedSlotServiceTest --pest --no-interaction`

Then replace with:

```php
<?php

use App\Models\FeaturedSlotHistory;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use App\Services\FeaturedSlotService;

beforeEach(function () {
    $this->service = app(FeaturedSlotService::class);
});

test('eligible movies excludes movies already in history', function () {
    $featured = Movie::factory()->published()->create(['tmdb_vote_average' => 8.0]);
    FeaturedSlotHistory::factory()->create(['movie_id' => $featured->id]);

    $eligible = Movie::factory()->published()->create(['tmdb_vote_average' => 7.0]);

    $result = $this->service->getEligibleMovies();

    expect($result->pluck('id')->toArray())->toContain($eligible->id)
        ->and($result->pluck('id')->toArray())->not->toContain($featured->id);
});

test('eligible movies excludes movies already in queue', function () {
    $queued = Movie::factory()->published()->create(['tmdb_vote_average' => 8.0]);
    FeaturedSlotQueue::factory()->create(['movie_id' => $queued->id]);

    $eligible = Movie::factory()->published()->create(['tmdb_vote_average' => 7.0]);

    $result = $this->service->getEligibleMovies();

    expect($result->pluck('id')->toArray())->toContain($eligible->id)
        ->and($result->pluck('id')->toArray())->not->toContain($queued->id);
});

test('eligible movies excludes draft and archived movies', function () {
    $draft = Movie::factory()->draft()->create(['tmdb_vote_average' => 9.0]);
    $archived = Movie::factory()->archived()->create(['tmdb_vote_average' => 9.0]);
    $published = Movie::factory()->published()->create(['tmdb_vote_average' => 5.0]);

    $result = $this->service->getEligibleMovies();

    expect($result->pluck('id')->toArray())
        ->toContain($published->id)
        ->not->toContain($draft->id)
        ->not->toContain($archived->id);
});

test('eligible movies ranked by tmdb_vote_average desc then created_at desc', function () {
    $low = Movie::factory()->published()->create(['tmdb_vote_average' => 5.0]);
    $high = Movie::factory()->published()->create(['tmdb_vote_average' => 9.0]);
    $mid = Movie::factory()->published()->create(['tmdb_vote_average' => 7.0]);

    $result = $this->service->getEligibleMovies();

    expect($result->first()->id)->toBe($high->id)
        ->and($result->values()[1]->id)->toBe($mid->id)
        ->and($result->last()->id)->toBe($low->id);
});

test('catalog exhaustion resets eligibility', function () {
    $movieA = Movie::factory()->published()->create(['tmdb_vote_average' => 9.0]);
    $movieB = Movie::factory()->published()->create(['tmdb_vote_average' => 7.0]);

    FeaturedSlotHistory::factory()->create(['movie_id' => $movieA->id]);
    FeaturedSlotHistory::factory()->create(['movie_id' => $movieB->id]);

    $result = $this->service->getEligibleMovies();

    expect($result)->toHaveCount(2)
        ->and($result->first()->id)->toBe($movieA->id);
});

test('fillQueue generates 4 weeks of entries per slot', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $this->service->fillQueue();

    $heroCount = FeaturedSlotQueue::slot('hero')->count();
    $pickCount = FeaturedSlotQueue::slot('pick_of_week')->count();

    expect($heroCount)->toBe(4)
        ->and($pickCount)->toBe(4);
});

test('fillQueue assigns different movies to hero and pick_of_week for same week', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $this->service->fillQueue();

    $weeks = FeaturedSlotQueue::query()->get()->groupBy('scheduled_for');
    foreach ($weeks as $entries) {
        $movieIds = $entries->pluck('movie_id')->toArray();
        expect(count($movieIds))->toBe(count(array_unique($movieIds)));
    }
});

test('fillQueue does not duplicate movies already in queue', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $this->service->fillQueue();
    $countBefore = FeaturedSlotQueue::count();

    $this->service->fillQueue();
    $countAfter = FeaturedSlotQueue::count();

    expect($countAfter)->toBe($countBefore);
});
```

**Step 2: Run tests to verify they fail**

Run: `ddev artisan test --compact tests/Feature/FeaturedSlotServiceTest.php`
Expected: FAIL — `FeaturedSlotService` class not found.

**Step 3: Write the service**

Create `app/Services/FeaturedSlotService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MovieStatus;
use App\Models\FeaturedSlotHistory;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FeaturedSlotService
{
    private const QUEUE_DEPTH = 4;

    private const SLOTS = ['pick_of_week', 'hero'];

    /**
     * Get eligible movies ranked by rating desc, created_at desc.
     * If all published movies have been featured, reset eligibility (catalog exhaustion).
     *
     * @return Collection<int, Movie>
     */
    public function getEligibleMovies(): Collection
    {
        $publishedCount = Movie::query()->published()->count();
        $featuredMovieIds = FeaturedSlotHistory::query()
            ->distinct()
            ->pluck('movie_id')
            ->filter()
            ->toArray();

        $exhausted = $publishedCount > 0 && count($featuredMovieIds) >= $publishedCount;

        $queuedMovieIds = FeaturedSlotQueue::query()->pluck('movie_id')->toArray();

        $query = Movie::query()
            ->published()
            ->whereNotIn('id', $queuedMovieIds);

        if (! $exhausted) {
            $query->whereNotIn('id', $featuredMovieIds);
        }

        return $query
            ->orderByDesc('tmdb_vote_average')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Fill the queue to QUEUE_DEPTH weeks ahead for each slot.
     */
    public function fillQueue(): void
    {
        foreach (self::SLOTS as $slot) {
            $existing = FeaturedSlotQueue::slot($slot)->count();
            $needed = self::QUEUE_DEPTH - $existing;

            if ($needed <= 0) {
                continue;
            }

            $lastScheduled = FeaturedSlotQueue::slot($slot)
                ->orderByDesc('scheduled_for')
                ->value('scheduled_for');

            $nextSunday = $lastScheduled
                ? Carbon::parse($lastScheduled)->next('Sunday')
                : $this->nextSunday();

            for ($i = 0; $i < $needed; $i++) {
                $scheduledFor = $nextSunday->copy()->addWeeks($i);
                $this->assignMovieToSlot($slot, $scheduledFor);
            }
        }
    }

    /**
     * Pick the next eligible movie for a slot on a given date and insert it.
     */
    private function assignMovieToSlot(string $slot, Carbon $scheduledFor): void
    {
        $otherSlotMovieId = FeaturedSlotQueue::query()
            ->where('scheduled_for', $scheduledFor->toDateString())
            ->where('slot', '!=', $slot)
            ->value('movie_id');

        $eligible = $this->getEligibleMovies();

        if ($otherSlotMovieId) {
            $eligible = $eligible->where('id', '!=', $otherSlotMovieId);
        }

        $movie = $eligible->first();
        if (! $movie) {
            return;
        }

        $nextPosition = (FeaturedSlotQueue::slot($slot)->max('position') ?? 0) + 1;

        FeaturedSlotQueue::create([
            'movie_id' => $movie->id,
            'slot' => $slot,
            'position' => $nextPosition,
            'selection_method' => 'auto',
            'scheduled_for' => $scheduledFor->toDateString(),
        ]);
    }

    private function nextSunday(): Carbon
    {
        $now = Carbon::now();

        return $now->isSunday() ? $now->copy() : $now->next('Sunday');
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `ddev artisan test --compact tests/Feature/FeaturedSlotServiceTest.php`
Expected: All 8 tests PASS.

**Step 5: Run Pint**

Run: `ddev exec vendor/bin/pint --dirty`

**Step 6: Commit**

```bash
git add -A && git commit -m "feat: add FeaturedSlotService with selection algorithm"
```

---

### Task 4: Create `featured:rotate` command

**Files:**
- Create: `app/Console/Commands/RotateFeaturedSlots.php`
- Create: `tests/Feature/RotateFeaturedSlotsCommandTest.php`

**Step 1: Write the failing tests**

Run: `ddev artisan make:test RotateFeaturedSlotsCommandTest --pest --no-interaction`

Then replace:

```php
<?php

use App\Models\FeaturedSlot;
use App\Models\FeaturedSlotHistory;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;

test('featured:rotate archives current slots to history', function () {
    $movie = Movie::factory()->published()->create();
    FeaturedSlot::factory()->for($movie)->create(['slot' => 'hero']);
    FeaturedSlotHistory::factory()->current()->create([
        'movie_id' => $movie->id,
        'slot' => 'hero',
    ]);

    $next = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create([
        'movie_id' => $next->id,
        'slot' => 'hero',
        'position' => 1,
    ]);
    FeaturedSlotQueue::factory()->create([
        'movie_id' => Movie::factory()->published()->create()->id,
        'slot' => 'pick_of_week',
        'position' => 1,
    ]);

    // Ensure enough movies for refill
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 5.0]);

    $this->artisan('featured:rotate')->assertSuccessful();

    $archived = FeaturedSlotHistory::where('movie_id', $movie->id)
        ->where('slot', 'hero')
        ->whereNotNull('ended_at')
        ->first();

    expect($archived)->not->toBeNull();
});

test('featured:rotate swaps in next queued movies', function () {
    // Current featured
    $currentHero = Movie::factory()->published()->create();
    $currentPick = Movie::factory()->published()->create();
    FeaturedSlot::factory()->for($currentHero)->create(['slot' => 'hero']);
    FeaturedSlot::factory()->for($currentPick)->create(['slot' => 'pick_of_week']);

    // Next in queue
    $nextHero = Movie::factory()->published()->create();
    $nextPick = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create(['movie_id' => $nextHero->id, 'slot' => 'hero', 'position' => 1]);
    FeaturedSlotQueue::factory()->create(['movie_id' => $nextPick->id, 'slot' => 'pick_of_week', 'position' => 1]);

    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 5.0]);

    $this->artisan('featured:rotate')->assertSuccessful();

    $heroSlot = FeaturedSlot::where('slot', 'hero')->first();
    $pickSlot = FeaturedSlot::where('slot', 'pick_of_week')->first();

    expect($heroSlot->movie_id)->toBe($nextHero->id)
        ->and($pickSlot->movie_id)->toBe($nextPick->id);
});

test('featured:rotate removes consumed queue entries and reindexes', function () {
    $movie1 = Movie::factory()->published()->create();
    $movie2 = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create(['movie_id' => $movie1->id, 'slot' => 'hero', 'position' => 1]);
    FeaturedSlotQueue::factory()->create(['movie_id' => $movie2->id, 'slot' => 'hero', 'position' => 2]);

    FeaturedSlotQueue::factory()->create([
        'movie_id' => Movie::factory()->published()->create()->id,
        'slot' => 'pick_of_week',
        'position' => 1,
    ]);

    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 5.0]);

    $this->artisan('featured:rotate')->assertSuccessful();

    expect(FeaturedSlotQueue::where('movie_id', $movie1->id)->exists())->toBeFalse();

    $remaining = FeaturedSlotQueue::slot('hero')->orderBy('position')->first();
    expect($remaining)->not->toBeNull()
        ->and($remaining->position)->toBe(1);
});

test('featured:rotate creates history entries for new slots', function () {
    $next = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create(['movie_id' => $next->id, 'slot' => 'hero', 'position' => 1]);
    FeaturedSlotQueue::factory()->create([
        'movie_id' => Movie::factory()->published()->create()->id,
        'slot' => 'pick_of_week',
        'position' => 1,
    ]);

    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 5.0]);

    $this->artisan('featured:rotate')->assertSuccessful();

    $history = FeaturedSlotHistory::where('movie_id', $next->id)
        ->where('slot', 'hero')
        ->current()
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->selection_method)->not->toBeNull();
});

test('featured:rotate auto-selects when queue is empty', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $this->artisan('featured:rotate')->assertSuccessful();

    expect(FeaturedSlot::where('slot', 'hero')->exists())->toBeTrue()
        ->and(FeaturedSlot::where('slot', 'pick_of_week')->exists())->toBeTrue();
});

test('featured:rotate keeps current slots when no eligible movies', function () {
    $movie = Movie::factory()->published()->create();
    FeaturedSlot::factory()->for($movie)->create(['slot' => 'hero']);

    // No other published movies, queue is empty
    $this->artisan('featured:rotate')->assertSuccessful();

    $heroSlot = FeaturedSlot::where('slot', 'hero')->first();
    expect($heroSlot->movie_id)->toBe($movie->id);
});

test('featured:rotate with --dry-run makes no changes', function () {
    $movie = Movie::factory()->published()->create();
    FeaturedSlot::factory()->for($movie)->create(['slot' => 'hero']);

    $next = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create(['movie_id' => $next->id, 'slot' => 'hero', 'position' => 1]);

    $this->artisan('featured:rotate --dry-run')->assertSuccessful();

    $heroSlot = FeaturedSlot::where('slot', 'hero')->first();
    expect($heroSlot->movie_id)->toBe($movie->id);
});
```

**Step 2: Run tests to verify they fail**

Run: `ddev artisan test --compact tests/Feature/RotateFeaturedSlotsCommandTest.php`
Expected: FAIL — command not found.

**Step 3: Write the command**

Run: `ddev artisan make:command RotateFeaturedSlots --no-interaction`

Then replace `app/Console/Commands/RotateFeaturedSlots.php`:

```php
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
```

**Step 4: Run tests to verify they pass**

Run: `ddev artisan test --compact tests/Feature/RotateFeaturedSlotsCommandTest.php`
Expected: All 7 tests PASS.

**Step 5: Run Pint**

Run: `ddev exec vendor/bin/pint --dirty`

**Step 6: Commit**

```bash
git add -A && git commit -m "feat: add featured:rotate command for weekly slot rotation"
```

---

### Task 5: Register the scheduler

**Files:**
- Modify: `routes/console.php`

**Step 1: Add the schedule entry**

Add to the end of `routes/console.php`:

```php
// Rotate featured slots every Sunday at 6 AM UTC
Schedule::command('featured:rotate')
    ->weeklyOn(0, '06:00')
    ->timezone('UTC')
    ->name('rotate-featured-slots')
    ->withoutOverlapping();
```

**Step 2: Verify the schedule is registered**

Run: `ddev artisan schedule:list`
Expected: Output should include `featured:rotate` scheduled for Sundays at 06:00.

**Step 3: Commit**

```bash
git add routes/console.php && git commit -m "feat: schedule featured:rotate every Sunday 6 AM UTC"
```

---

### Task 6: Create `FeaturedSlotHistoryResource`

**Files:**
- Create: `app/Http/Resources/FeaturedSlotHistoryResource.php`

**Step 1: Create the resource**

Run: `ddev artisan make:resource FeaturedSlotHistoryResource --no-interaction`

Then replace:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\FeaturedSlotHistory
 */
class FeaturedSlotHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'movie_id' => $this->movie_id,
            'slot' => $this->slot,
            'selection_method' => $this->selection_method,
            'started_at' => $this->started_at->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'movie' => new MovieResource($this->whenLoaded('movie')),
        ];
    }
}
```

**Step 2: Commit**

```bash
git add -A && git commit -m "feat: add FeaturedSlotHistoryResource"
```

---

### Task 7: Create `FeaturedQueueController` and admin routes

**Files:**
- Create: `app/Http/Controllers/Admin/FeaturedQueueController.php`
- Modify: `routes/web/admin.php`
- Create: `tests/Feature/FeaturedQueueControllerTest.php`

**Step 1: Write the failing tests**

Run: `ddev artisan make:test FeaturedQueueControllerTest --pest --no-interaction`

Then replace:

```php
<?php

use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

test('queue index requires admin', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard.featured-queue'))
        ->assertForbidden();
});

test('queue index shows queued entries grouped by slot', function () {
    $movie = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create([
        'movie_id' => $movie->id,
        'slot' => 'hero',
        'position' => 1,
    ]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-queue'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/FeaturedSlots/Queue')
            ->has('heroQueue')
            ->has('pickOfWeekQueue')
        );
});

test('queue store adds manual entry and shifts positions', function () {
    $existing = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create([
        'movie_id' => $existing->id,
        'slot' => 'hero',
        'position' => 1,
        'scheduled_for' => now()->next('Sunday'),
    ]);

    $newMovie = Movie::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.store'), [
            'movie_id' => $newMovie->id,
            'slot' => 'hero',
            'position' => 1,
        ])
        ->assertRedirect();

    $inserted = FeaturedSlotQueue::where('movie_id', $newMovie->id)->first();
    expect($inserted->position)->toBe(1)
        ->and($inserted->selection_method)->toBe('manual');

    $shifted = FeaturedSlotQueue::where('movie_id', $existing->id)->first();
    expect($shifted->position)->toBe(2);
});

test('queue store validates published movies only', function () {
    $draft = Movie::factory()->draft()->create();

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.store'), [
            'movie_id' => $draft->id,
            'slot' => 'hero',
            'position' => 1,
        ])
        ->assertSessionHasErrors('movie_id');
});

test('queue destroy removes entry and reindexes', function () {
    $movie1 = Movie::factory()->published()->create();
    $movie2 = Movie::factory()->published()->create();

    $entry1 = FeaturedSlotQueue::factory()->create(['movie_id' => $movie1->id, 'slot' => 'hero', 'position' => 1]);
    FeaturedSlotQueue::factory()->create(['movie_id' => $movie2->id, 'slot' => 'hero', 'position' => 2]);

    $this->actingAs($this->admin)
        ->delete(route('dashboard.featured-queue.destroy', $entry1))
        ->assertRedirect();

    expect(FeaturedSlotQueue::find($entry1->id))->toBeNull();

    $remaining = FeaturedSlotQueue::where('movie_id', $movie2->id)->first();
    expect($remaining->position)->toBe(1);
});

test('queue refill triggers service fillQueue', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.refill'))
        ->assertRedirect();

    expect(FeaturedSlotQueue::count())->toBeGreaterThan(0);
});
```

**Step 2: Run tests to verify they fail**

Run: `ddev artisan test --compact tests/Feature/FeaturedQueueControllerTest.php`
Expected: FAIL — route not defined.

**Step 3: Write the controller**

Run: `ddev artisan make:controller Admin/FeaturedQueueController --no-interaction`

Then replace:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use App\Services\FeaturedSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedQueueController extends Controller
{
    public function index(): Response
    {
        $heroQueue = FeaturedSlotQueue::slot('hero')
            ->with('movie')
            ->orderBy('position')
            ->get();

        $pickOfWeekQueue = FeaturedSlotQueue::slot('pick_of_week')
            ->with('movie')
            ->orderBy('position')
            ->get();

        $movies = Movie::query()
            ->published()
            ->orderBy('title')
            ->get(['id', 'title', 'release_year']);

        return Inertia::render('Admin/FeaturedSlots/Queue', [
            'heroQueue' => $heroQueue,
            'pickOfWeekQueue' => $pickOfWeekQueue,
            'movies' => $movies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'movie_id' => [
                'required',
                'exists:movies,id',
                Rule::exists('movies', 'id')->where('status', MovieStatus::Published->value),
            ],
            'slot' => 'required|in:hero,pick_of_week',
            'position' => 'required|integer|min:1',
        ]);

        $slot = $validated['slot'];
        $position = (int) $validated['position'];

        // Shift existing entries down
        FeaturedSlotQueue::slot($slot)
            ->where('position', '>=', $position)
            ->orderByDesc('position')
            ->get()
            ->each(function (FeaturedSlotQueue $entry): void {
                $entry->update(['position' => $entry->position + 1]);
            });

        // Calculate scheduled_for based on position
        $scheduledFor = now()->next('Sunday')->addWeeks($position - 1);

        FeaturedSlotQueue::create([
            'movie_id' => $validated['movie_id'],
            'slot' => $slot,
            'position' => $position,
            'selection_method' => 'manual',
            'scheduled_for' => $scheduledFor->toDateString(),
        ]);

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Movie added to queue.');
    }

    public function destroy(FeaturedSlotQueue $featuredSlotQueue): RedirectResponse
    {
        $slot = $featuredSlotQueue->slot;
        $position = $featuredSlotQueue->position;

        $featuredSlotQueue->delete();

        // Reindex positions
        FeaturedSlotQueue::slot($slot)
            ->where('position', '>', $position)
            ->orderBy('position')
            ->get()
            ->each(function (FeaturedSlotQueue $entry): void {
                $entry->update(['position' => $entry->position - 1]);
            });

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Entry removed from queue.');
    }

    public function refill(FeaturedSlotService $service): RedirectResponse
    {
        $service->fillQueue();

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Queue refilled.');
    }
}
```

**Step 4: Register the routes**

Add to `routes/web/admin.php` inside the `northcloud-admin` middleware group, after the featured slots section:

```php
    // Featured slot queue
    Route::get('/dashboard/featured-queue', [\App\Http\Controllers\Admin\FeaturedQueueController::class, 'index'])
        ->name('dashboard.featured-queue');
    Route::post('/dashboard/featured-queue', [\App\Http\Controllers\Admin\FeaturedQueueController::class, 'store'])
        ->name('dashboard.featured-queue.store');
    Route::delete('/dashboard/featured-queue/{featuredSlotQueue}', [\App\Http\Controllers\Admin\FeaturedQueueController::class, 'destroy'])
        ->name('dashboard.featured-queue.destroy');
    Route::post('/dashboard/featured-queue/refill', [\App\Http\Controllers\Admin\FeaturedQueueController::class, 'refill'])
        ->name('dashboard.featured-queue.refill');
```

Add the import at the top of the file:
```php
use App\Http\Controllers\Admin\FeaturedQueueController;
```

**Step 5: Run tests to verify they pass**

Run: `ddev artisan test --compact tests/Feature/FeaturedQueueControllerTest.php`
Expected: All 6 tests PASS.

**Step 6: Run Pint**

Run: `ddev exec vendor/bin/pint --dirty`

**Step 7: Commit**

```bash
git add -A && git commit -m "feat: add FeaturedQueueController with admin routes"
```

---

### Task 8: Create `FeaturedHistoryController` and admin routes

**Files:**
- Create: `app/Http/Controllers/Admin/FeaturedHistoryController.php`
- Modify: `routes/web/admin.php`
- Create: `tests/Feature/FeaturedHistoryControllerTest.php`

**Step 1: Write the failing tests**

Run: `ddev artisan make:test FeaturedHistoryControllerTest --pest --no-interaction`

Then replace:

```php
<?php

use App\Models\FeaturedSlotHistory;
use App\Models\Movie;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

test('history index requires admin', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard.featured-history'))
        ->assertForbidden();
});

test('history index returns paginated history', function () {
    $movie = Movie::factory()->published()->create();
    FeaturedSlotHistory::factory()->create(['movie_id' => $movie->id]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/FeaturedSlots/History')
            ->has('history.data', 1)
            ->has('history.meta')
        );
});

test('history index filters by slot type', function () {
    FeaturedSlotHistory::factory()->create(['slot' => 'hero']);
    FeaturedSlotHistory::factory()->create(['slot' => 'pick_of_week']);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history', ['slot' => 'hero']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('history.data', 1)
            ->where('history.data.0.slot', 'hero')
        );
});

test('history index filters by selection method', function () {
    FeaturedSlotHistory::factory()->create(['selection_method' => 'auto']);
    FeaturedSlotHistory::factory()->create(['selection_method' => 'manual']);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history', ['method' => 'manual']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('history.data', 1)
            ->where('history.data.0.selection_method', 'manual')
        );
});

test('history index sorts by started_at desc by default', function () {
    FeaturedSlotHistory::factory()->create(['started_at' => now()->subWeeks(2)]);
    FeaturedSlotHistory::factory()->create(['started_at' => now()->subWeek()]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('history.data', 2)
        );
});
```

**Step 2: Run tests to verify they fail**

Run: `ddev artisan test --compact tests/Feature/FeaturedHistoryControllerTest.php`
Expected: FAIL — route not defined.

**Step 3: Write the controller**

Run: `ddev artisan make:controller Admin/FeaturedHistoryController --no-interaction`

Then replace:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedSlotHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedHistoryController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public function index(Request $request): Response
    {
        $query = FeaturedSlotHistory::query()->with('movie');

        if ($request->filled('slot')) {
            $query->where('slot', $request->get('slot'));
        }

        if ($request->filled('method')) {
            $query->where('selection_method', $request->get('method'));
        }

        $sort = $request->get('sort', 'started_at_desc');
        match ($sort) {
            'started_at_asc' => $query->oldest('started_at'),
            'ended_at_desc' => $query->latest('ended_at'),
            'ended_at_asc' => $query->oldest('ended_at'),
            default => $query->latest('started_at'),
        };

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/FeaturedSlots/History', [
            'history' => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'links' => $paginator->linkCollection()->toArray(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
            ],
            'queryParams' => $request->only(['sort', 'per_page', 'slot', 'method']),
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->get('per_page', 20);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 20;
    }
}
```

**Step 4: Register the routes**

Add to `routes/web/admin.php` inside the middleware group, after the queue routes:

```php
    // Featured slot history
    Route::get('/dashboard/featured-history', [FeaturedHistoryController::class, 'index'])
        ->name('dashboard.featured-history');
```

Add the import:
```php
use App\Http\Controllers\Admin\FeaturedHistoryController;
```

**Step 5: Run tests to verify they pass**

Run: `ddev artisan test --compact tests/Feature/FeaturedHistoryControllerTest.php`
Expected: All 5 tests PASS.

**Step 6: Run Pint**

Run: `ddev exec vendor/bin/pint --dirty`

**Step 7: Commit**

```bash
git add -A && git commit -m "feat: add FeaturedHistoryController with admin routes"
```

---

### Task 9: Add TypeScript types for queue and history

**Files:**
- Modify: `resources/js/types/models.ts`

**Step 1: Add the new interfaces**

Add after the existing `FeaturedSlot` interface:

```typescript
export interface FeaturedSlotQueue {
    id: number;
    movie_id: number;
    slot: 'hero' | 'pick_of_week';
    position: number;
    selection_method: 'auto' | 'manual';
    scheduled_for: string;
    created_at: string;
    updated_at: string;
    movie?: Movie;
}

export interface FeaturedSlotHistory {
    id: number;
    movie_id: number | null;
    slot: 'hero' | 'pick_of_week';
    selection_method: 'auto' | 'manual';
    started_at: string;
    ended_at: string | null;
    created_at: string;
    updated_at: string;
    movie?: Movie | null;
}
```

**Step 2: Commit**

```bash
git add resources/js/types/models.ts && git commit -m "feat: add TypeScript types for queue and history"
```

---

### Task 10: Create Queue admin page (Vue)

**Files:**
- Create: `resources/js/pages/Admin/FeaturedSlots/Queue.vue`

**Step 1: Create the Vue component**

```vue
<script setup lang="ts">
import type { FeaturedSlotQueue, Movie } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    heroQueue: FeaturedSlotQueue[];
    pickOfWeekQueue: FeaturedSlotQueue[];
    movies: Pick<Movie, 'id' | 'title' | 'release_year'>[];
}

const props = defineProps<Props>();

const showAddForm = ref(false);
const addSlot = ref<'hero' | 'pick_of_week'>('hero');
const addMovieId = ref<number | null>(null);
const addPosition = ref(1);

function submitAdd() {
    if (!addMovieId.value) return;
    router.post(route('dashboard.featured-queue.store'), {
        movie_id: addMovieId.value,
        slot: addSlot.value,
        position: addPosition.value,
    }, {
        onSuccess: () => {
            showAddForm.value = false;
            addMovieId.value = null;
            addPosition.value = 1;
        },
    });
}

function removeEntry(entry: FeaturedSlotQueue) {
    if (!confirm(`Remove "${entry.movie?.title}" from the queue?`)) return;
    router.delete(route('dashboard.featured-queue.destroy', { featuredSlotQueue: entry.id }));
}

function refillQueue() {
    router.post(route('dashboard.featured-queue.refill'));
}

function slotLabel(slot: string): string {
    return slot === 'hero' ? 'Hero' : 'Pick of the Week';
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Featured Slot Queue" />

        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Featured Slot Queue</h1>
                    <p class="mt-2 text-zinc-400">Upcoming featured slot rotations — next change every Sunday 6 AM UTC</p>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('dashboard.featured-slots')"
                        class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
                    >
                        Now Showing
                    </Link>
                    <button
                        @click="refillQueue"
                        class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
                    >
                        Refill Queue
                    </button>
                    <button
                        @click="showAddForm = !showAddForm"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    >
                        Add Override
                    </button>
                </div>
            </div>

            <!-- Add Override Form -->
            <div v-if="showAddForm" class="mb-8 rounded-lg border border-zinc-700 bg-zinc-900 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Add Manual Override</h2>
                <form @submit.prevent="submitAdd" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-300">Movie</label>
                        <select v-model="addMovieId" class="mt-1 rounded-lg border-zinc-700 bg-zinc-800 text-white">
                            <option :value="null" disabled>Select movie...</option>
                            <option v-for="movie in movies" :key="movie.id" :value="movie.id">
                                {{ movie.title }} ({{ movie.release_year }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300">Slot</label>
                        <select v-model="addSlot" class="mt-1 rounded-lg border-zinc-700 bg-zinc-800 text-white">
                            <option value="hero">Hero</option>
                            <option value="pick_of_week">Pick of the Week</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300">Position</label>
                        <input v-model.number="addPosition" type="number" min="1" class="mt-1 w-20 rounded-lg border-zinc-700 bg-zinc-800 text-white" />
                    </div>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Insert
                    </button>
                    <button type="button" @click="showAddForm = false" class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800">
                        Cancel
                    </button>
                </form>
            </div>

            <!-- Queue Columns -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div v-for="(queue, slotType) in { hero: heroQueue, pick_of_week: pickOfWeekQueue }" :key="slotType">
                    <h2 class="mb-4 text-xl font-semibold text-white">{{ slotLabel(slotType) }}</h2>
                    <div v-if="queue.length === 0" class="rounded-lg border border-zinc-800 bg-zinc-900 p-6 text-center text-zinc-500">
                        Queue empty — click "Refill Queue" to auto-populate.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="entry in queue"
                            :key="entry.id"
                            class="flex items-center gap-4 rounded-lg border border-zinc-800 bg-zinc-900 p-4"
                        >
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-800 text-sm font-bold text-white">
                                {{ entry.position }}
                            </span>
                            <img
                                v-if="entry.movie?.poster_url"
                                :src="entry.movie.poster_url"
                                :alt="entry.movie.title"
                                class="h-12 w-8 shrink-0 rounded object-cover"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-white">{{ entry.movie?.title ?? 'Unknown' }}</p>
                                <p class="text-sm text-zinc-500">{{ entry.scheduled_for }}</p>
                            </div>
                            <span
                                :class="entry.selection_method === 'manual'
                                    ? 'bg-amber-500/10 text-amber-400'
                                    : 'bg-emerald-500/10 text-emerald-400'"
                                class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                            >
                                {{ entry.selection_method }}
                            </span>
                            <button
                                @click="removeEntry(entry)"
                                class="shrink-0 text-zinc-500 hover:text-red-400"
                                title="Remove from queue"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Link to History -->
            <div class="mt-12 text-center">
                <Link
                    :href="route('dashboard.featured-history')"
                    class="text-sm text-zinc-400 hover:text-white"
                >
                    View rotation history &rarr;
                </Link>
            </div>
        </div>
    </AppSidebarLayout>
</template>
```

**Step 2: Run build to check for compilation errors**

Run: `cd /home/fsd42/dev/movies-of-war.com && npm run build`
Expected: Build succeeds without errors.

**Step 3: Commit**

```bash
git add -A && git commit -m "feat: add Queue admin page for featured slot preview"
```

---

### Task 11: Create History admin page (Vue)

**Files:**
- Create: `resources/js/pages/Admin/FeaturedSlots/History.vue`

**Step 1: Create the Vue component**

```vue
<script setup lang="ts">
import type { FeaturedSlotHistory } from '@/types/models';
import type { PaginationMeta } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    history: {
        data: FeaturedSlotHistory[];
        meta: PaginationMeta;
    };
    queryParams: {
        sort?: string;
        per_page?: number;
        slot?: string;
        method?: string;
    };
}

const props = defineProps<Props>();

function applyFilter(key: string, value: string | null) {
    const params: Record<string, string | null> = { ...props.queryParams, [key]: value };
    if (!value) {
        delete params[key];
    }
    router.get(route('dashboard.featured-history'), params, { preserveState: true });
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function slotLabel(slot: string): string {
    return slot === 'hero' ? 'Hero' : 'Pick of the Week';
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Featured Slot History" />

        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Featured Slot History</h1>
                    <p class="mt-2 text-zinc-400">Complete log of all featured slot rotations</p>
                </div>
                <Link
                    :href="route('dashboard.featured-queue')"
                    class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
                >
                    View Queue
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-3">
                <select
                    :value="queryParams.slot ?? ''"
                    @change="applyFilter('slot', ($event.target as HTMLSelectElement).value || null)"
                    class="rounded-lg border-zinc-700 bg-zinc-900 text-sm text-white"
                >
                    <option value="">All Slots</option>
                    <option value="hero">Hero</option>
                    <option value="pick_of_week">Pick of the Week</option>
                </select>
                <select
                    :value="queryParams.method ?? ''"
                    @change="applyFilter('method', ($event.target as HTMLSelectElement).value || null)"
                    class="rounded-lg border-zinc-700 bg-zinc-900 text-sm text-white"
                >
                    <option value="">All Methods</option>
                    <option value="auto">Auto</option>
                    <option value="manual">Manual</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-zinc-800">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-800 bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 font-medium text-zinc-400">Movie</th>
                            <th class="px-4 py-3 font-medium text-zinc-400">Slot</th>
                            <th class="px-4 py-3 font-medium text-zinc-400">Method</th>
                            <th class="px-4 py-3 font-medium text-zinc-400">Started</th>
                            <th class="px-4 py-3 font-medium text-zinc-400">Ended</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <tr v-for="entry in history.data" :key="entry.id" class="bg-zinc-950">
                            <td class="px-4 py-3 text-white">
                                {{ entry.movie?.title ?? 'Deleted Movie' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="entry.slot === 'hero'
                                        ? 'bg-red-500/10 text-red-400'
                                        : 'bg-blue-500/10 text-blue-400'"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ slotLabel(entry.slot) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="entry.selection_method === 'manual'
                                        ? 'text-amber-400'
                                        : 'text-emerald-400'"
                                    class="text-xs font-medium"
                                >
                                    {{ entry.selection_method }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-400">{{ formatDate(entry.started_at) }}</td>
                            <td class="px-4 py-3 text-zinc-400">
                                <template v-if="entry.ended_at">{{ formatDate(entry.ended_at) }}</template>
                                <span v-else class="text-emerald-400">Current</span>
                            </td>
                        </tr>
                        <tr v-if="history.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">No history yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="history.meta.last_page > 1" class="mt-6 flex justify-center gap-1">
                <template v-for="link in history.meta.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-red-600 text-white' : 'text-zinc-400 hover:bg-zinc-800'"
                        v-html="link.label"
                    />
                    <span v-else class="px-3 py-1 text-sm text-zinc-600" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppSidebarLayout>
</template>
```

**Step 2: Run build**

Run: `cd /home/fsd42/dev/movies-of-war.com && npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add -A && git commit -m "feat: add History admin page for featured slot analytics"
```

---

### Task 12: Add cross-links from existing Featured Slots page

**Files:**
- Modify: `resources/js/pages/Admin/FeaturedSlots/Index.vue`

**Step 1: Add navigation links**

Add a "View Queue" and "View History" link near the existing "Add Featured Slot" button in the header area of `Index.vue`. The exact location depends on the current markup — add link buttons:

```vue
<Link
    :href="route('dashboard.featured-queue')"
    class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
>
    View Queue
</Link>
<Link
    :href="route('dashboard.featured-history')"
    class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
>
    View History
</Link>
```

**Step 2: Run build**

Run: `cd /home/fsd42/dev/movies-of-war.com && npm run build`

**Step 3: Commit**

```bash
git add -A && git commit -m "feat: add queue and history links to featured slots page"
```

---

### Task 13: Run Wayfinder and full test suite

**Step 1: Generate Wayfinder routes**

Run: `ddev artisan wayfinder:generate`

**Step 2: Run frontend linting**

Run: `npm run check`

**Step 3: Run Pint on all changed files**

Run: `ddev exec vendor/bin/pint --dirty`

**Step 4: Run the full test suite**

Run: `ddev artisan test --compact`
Expected: All tests pass, including existing HomeTest and new tests.

**Step 5: Commit any generated/fixed files**

```bash
git add -A && git commit -m "chore: regenerate wayfinder routes and fix lint"
```

---

### Task 14: Verify end-to-end manually

**Step 1: Seed some queue data**

Run via tinker:
```bash
ddev artisan tinker --execute="App\Services\FeaturedSlotService::class |> app()->make(...)->fillQueue()"
```

Or use the admin UI: navigate to `/dashboard/featured-queue` and click "Refill Queue."

**Step 2: Test dry run**

Run: `ddev artisan featured:rotate --dry-run`
Expected: Output shows what would be swapped without making changes.

**Step 3: Test actual rotation**

Run: `ddev artisan featured:rotate`
Expected: Slots rotate, history entries created, queue refilled.

**Step 4: Verify homepage**

Check that the homepage displays the newly rotated featured movies.

---

Plan complete and saved to `docs/plans/2026-02-26-featured-slot-queue.md`. Two execution options:

**1. Subagent-Driven (this session)** — I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** — Open a new session with executing-plans, batch execution with checkpoints

Which approach?