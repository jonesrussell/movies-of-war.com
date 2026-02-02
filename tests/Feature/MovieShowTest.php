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
