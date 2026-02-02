<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('non-admin dashboard returns user stats and recent reviews', function () {
    $user = User::factory()->create(['is_admin' => false]);
    Movie::factory()->published()->count(3)->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('stats')
        ->where('stats.reviews_count', 0)
        ->where('stats.watchlist_count', 0)
        ->has('recentMovies')
        ->has('recentReviews')
        ->where('recentReviews', [])
    );
});

test('non-admin dashboard includes recent reviews when user has reviews', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);
    Review::factory()->for($user)->for($movie)->create([
        'title' => 'My review',
        'content' => 'Great film!',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('stats.reviews_count', 1)
        ->has('recentReviews')
        ->has('recentReviews.0')
        ->where('recentReviews.0.title', 'My review')
    );
});
