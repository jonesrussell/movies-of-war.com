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
