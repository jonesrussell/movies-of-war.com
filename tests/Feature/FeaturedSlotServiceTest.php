<?php

use App\Enums\SlotType;
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

test('eligible movies returns empty collection when no published movies exist', function () {
    $result = $this->service->getEligibleMovies();

    expect($result)->toHaveCount(0);
});

test('fillQueue generates 4 weeks of entries per slot', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $this->service->fillQueue();

    $heroCount = FeaturedSlotQueue::slot('hero')->count();
    $pickCount = FeaturedSlotQueue::slot('pick_of_week')->count();

    expect($heroCount)->toBe(4)
        ->and($pickCount)->toBe(4);
});

test('fillQueue returns count of entries added', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $added = $this->service->fillQueue();

    expect($added)->toBe(8); // 4 per slot x 2 slots
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

test('fillQueue with no published movies creates no queue entries', function () {
    $added = $this->service->fillQueue();

    expect(FeaturedSlotQueue::count())->toBe(0)
        ->and($added)->toBe(0);
});

test('fillQueue handles fewer eligible movies than queue depth gracefully', function () {
    Movie::factory()->published()->count(3)->create(['tmdb_vote_average' => 7.0]);

    $added = $this->service->fillQueue();

    $total = FeaturedSlotQueue::count();
    expect($total)->toBeLessThanOrEqual(3)
        ->and($total)->toBeGreaterThan(0)
        ->and($added)->toBe($total);
});

// =========================================================================
// Slot Strategy Tests
// =========================================================================

test('hero slot selects upcoming movie over higher-rated classic', function () {
    Movie::factory()->published()->create([
        'tmdb_vote_average' => 9.5,
        'is_upcoming' => false,
    ]);
    $upcoming = Movie::factory()->published()->upcoming()->create([
        'tmdb_vote_average' => 6.0,
    ]);

    $result = $this->service->getEligibleMoviesForSlot(SlotType::Hero);

    expect($result->first()->id)->toBe($upcoming->id);
});

test('pick of week slot selects highest-rated regardless of upcoming status', function () {
    $classic = Movie::factory()->published()->create([
        'tmdb_vote_average' => 9.5,
        'is_upcoming' => false,
    ]);
    Movie::factory()->published()->upcoming()->create([
        'tmdb_vote_average' => 6.0,
    ]);

    $result = $this->service->getEligibleMoviesForSlot(SlotType::PickOfWeek);

    expect($result->first()->id)->toBe($classic->id);
});

test('hero slot falls back to highest-rated when no upcoming movies exist', function () {
    $highRated = Movie::factory()->published()->create([
        'tmdb_vote_average' => 9.0,
        'is_upcoming' => false,
    ]);
    Movie::factory()->published()->create([
        'tmdb_vote_average' => 6.0,
        'is_upcoming' => false,
    ]);

    $result = $this->service->getEligibleMoviesForSlot(SlotType::Hero);

    expect($result->first()->id)->toBe($highRated->id);
});

test('upcoming strategy orders by soonest release date first', function () {
    $later = Movie::factory()->published()->create([
        'is_upcoming' => true,
        'release_date' => now()->addMonths(6),
        'tmdb_vote_average' => 8.0,
    ]);
    $sooner = Movie::factory()->published()->create([
        'is_upcoming' => true,
        'release_date' => now()->addMonth(),
        'tmdb_vote_average' => 5.0,
    ]);

    $result = $this->service->getEligibleMoviesForSlot(SlotType::Hero);

    expect($result->first()->id)->toBe($sooner->id)
        ->and($result->values()[1]->id)->toBe($later->id);
});

test('fillQueue uses different strategies per slot', function () {
    // Create enough non-upcoming movies for PickOfWeek to consume without taking the upcoming one
    Movie::factory()->published()->count(8)->create([
        'tmdb_vote_average' => 8.0,
        'is_upcoming' => false,
    ]);
    $upcoming = Movie::factory()->published()->upcoming()->create([
        'tmdb_vote_average' => 5.0,
        'release_date' => now()->addMonth(),
    ]);

    $this->service->fillQueue();

    $heroMovie = FeaturedSlotQueue::slot(SlotType::Hero)
        ->orderBy('position')
        ->first();
    $pickMovie = FeaturedSlotQueue::slot(SlotType::PickOfWeek)
        ->orderBy('position')
        ->first();

    // Hero should pick the upcoming movie (upcoming strategy)
    expect($heroMovie->movie_id)->toBe($upcoming->id);
    // PickOfWeek should pick a higher-rated non-upcoming movie (highest-rated strategy)
    expect($pickMovie->movie_id)->not->toBe($upcoming->id);
});
