<?php

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('movie show page returns tmdb rating director and writers when set', function () {
    $movie = Movie::factory()->published()->create([
        'slug' => 'jarhead',
        'tmdb_vote_average' => 7.2,
        'tmdb_vote_count' => 3500,
        'director' => 'Sam Mendes',
        'writers' => ['William Broyles Jr.'],
    ]);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('movie')
        ->where('movie.tmdb_vote_average', 7.2)
        ->where('movie.tmdb_vote_count', 3500)
        ->where('movie.director', 'Sam Mendes')
        ->where('movie.writers', ['William Broyles Jr.'])
    );
});

test('movie show page does not break when tmdb rating director and writers are null', function () {
    $movie = Movie::factory()->published()->create([
        'slug' => 'minimal-movie',
        'tmdb_vote_average' => null,
        'tmdb_vote_count' => null,
        'director' => null,
        'writers' => null,
    ]);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->where('movie.slug', 'minimal-movie')
    );
});

test('movie show page with no reviews returns reviews data for guest empty state', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'no-reviews-movie']);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('movie')
        ->has('reviews')
        ->where('reviews.summary.user_rating_count', 0)
        ->has('auth')
    );
});

test('movie show page includes curator review and is_curator when curator is configured', function () {
    $curator = User::factory()->create(['name' => 'Russell Jones']);
    Config::set('app.curator_user_id', $curator->id);

    $movie = Movie::factory()->published()->create(['slug' => 'curator-movie']);
    $review = Review::factory()->for($curator)->for($movie)->create([
        'title' => 'Curator pick',
        'content' => 'The curator says this is essential viewing.',
        'rating' => 4,
    ]);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('reviews.curator_review')
        ->where('reviews.curator_review.id', $review->id)
        ->where('reviews.curator_review.is_curator', true)
        ->where('reviews.curator_review.user.name', 'Russell Jones')
    );
});

test('movie show page includes user review in main content when user has reviewed', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->published()->create(['slug' => 'user-reviewed-movie']);
    Review::factory()->for($user)->for($movie)->create([
        'rating' => 2.0,
        'content' => 'My review of this film.',
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('reviews.user_review')
        ->where('reviews.user_review.rating', 2)
    );
});

test('movie show page includes filesystem curator review when review file exists', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'fs-review-movie']);

    $reviewContent = <<<'MD'
---
title: "FS Review Movie"
year: 2024
rating: 3.5
director: "Test Director"
starring: ["Actor A"]
runtime: 100
---

A filesystem curator review.
MD;

    $path = resource_path('reviews/fs-review-movie.md');
    file_put_contents($path, $reviewContent);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('curatorReview')
        ->where('curatorReview.title', 'FS Review Movie')
        ->where('curatorReview.rating', 3.5)
        ->where('curatorReview.director', 'Test Director')
    );

    @unlink($path);
});

test('movie show page has null curatorReview when no review file exists', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'no-fs-review']);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->where('curatorReview', null)
    );
});
