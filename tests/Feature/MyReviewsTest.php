<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

test('guests cannot access my reviews page', function () {
    $response = $this->get(route('my-reviews.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user sees only their reviews on my reviews page', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $movie = Movie::factory()->published()->create(['slug' => 'my-movie']);
    $otherMovie = Movie::factory()->published()->create(['slug' => 'other-movie']);

    Review::factory()->for($user)->for($movie)->create([
        'title' => 'My review',
        'content' => 'I wrote this.',
    ]);
    Review::factory()->for($otherUser)->for($otherMovie)->create([
        'title' => 'Other review',
        'content' => 'Someone else wrote this.',
    ]);

    $response = $this->actingAs($user)->get(route('my-reviews.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('MyReviews/Index')
        ->has('reviews')
        ->has('reviews.data', 1)
        ->where('reviews.data.0.title', 'My review')
    );
});

test('my reviews page shows empty state when user has no reviews', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('my-reviews.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('MyReviews/Index')
        ->has('reviews')
        ->has('reviews.data')
        ->where('reviews.data', [])
    );
});
