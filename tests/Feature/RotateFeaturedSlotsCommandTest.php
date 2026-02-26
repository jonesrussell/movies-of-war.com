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
