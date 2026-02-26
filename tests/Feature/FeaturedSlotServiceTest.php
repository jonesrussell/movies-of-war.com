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
