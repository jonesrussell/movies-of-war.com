<?php

use App\Models\Movie;

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
        ->has('movie')
        ->where('movie.slug', 'minimal-movie')
    );
});
