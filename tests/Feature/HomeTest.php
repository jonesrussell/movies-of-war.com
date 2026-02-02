<?php

use App\Models\FeaturedSlot;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('home page returns pick of week and hero movie', function () {
    $pickMovie = Movie::factory()->published()->create(['slug' => 'pick-movie']);
    $heroMovie = Movie::factory()->published()->create(['slug' => 'hero-movie']);

    FeaturedSlot::factory()->for($pickMovie)->create(['slot' => 'pick_of_week']);
    FeaturedSlot::factory()->for($heroMovie)->create(['slot' => 'hero']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('pickOfWeekMovie')
        ->where('pickOfWeekMovie.slug', 'pick-movie')
        ->has('heroMovie')
        ->where('heroMovie.slug', 'hero-movie')
    );
});

test('home page includes pick of week review when curator has reviewed', function () {
    $curator = User::factory()->create();
    Config::set('app.curator_user_id', $curator->id);

    $movie = Movie::factory()->published()->create(['slug' => 'reviewed-pick']);
    FeaturedSlot::factory()->for($movie)->create(['slot' => 'pick_of_week']);

    Review::factory()->for($curator)->for($movie)->create([
        'rating' => 4,
        'content' => 'A stellar war film that captures the essence of combat.',
        'is_published' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('pickOfWeekMovie')
        ->has('pickOfWeekReview')
        ->where('pickOfWeekReview.rating', 4)
        ->where('pickOfWeekMovie.slug', 'reviewed-pick')
    );
});

test('home page does not include pick of week review when curator has not reviewed', function () {
    $curator = User::factory()->create();
    Config::set('app.curator_user_id', $curator->id);

    $movie = Movie::factory()->published()->create(['slug' => 'unreviewed-pick']);
    FeaturedSlot::factory()->for($movie)->create(['slot' => 'pick_of_week']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('pickOfWeekMovie')
        ->where('pickOfWeekReview', null)
    );
});

test('home page includes pick of week user review when authenticated user has reviewed', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->published()->create(['slug' => 'my-pick', 'is_upcoming' => false]);
    FeaturedSlot::factory()->for($movie)->create(['slot' => 'pick_of_week']);
    Review::factory()->for($user)->for($movie)->create([
        'rating' => 2.0,
        'content' => 'My take on this film.',
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('pickOfWeekUserReview')
        ->where('pickOfWeekUserReview.rating', 2.0)
        ->where('pickOfWeekUserReview.movie.slug', 'my-pick')
    );
});

test('guest users never receive user_review on home page', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'latest-one', 'is_upcoming' => false]);
    FeaturedSlot::factory()->for($movie)->create(['slot' => 'pick_of_week']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $props = $response->original->getData()['page']['props'] ?? [];

    foreach ($props['latestMovies'] ?? [] as $m) {
        expect($m)->not->toHaveKey('user_review');
    }
    foreach ($props['upcomingMovies'] ?? [] as $m) {
        expect($m)->not->toHaveKey('user_review');
    }
    if (isset($props['heroMovie']) && $props['heroMovie'] !== null) {
        expect($props['heroMovie'])->not->toHaveKey('user_review');
    }
    if (isset($props['pickOfWeekMovie']) && $props['pickOfWeekMovie'] !== null) {
        expect($props['pickOfWeekMovie'])->not->toHaveKey('user_review');
    }
});
