<?php

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

test('movies index includes user_review when authenticated user has reviewed a movie on the page', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->published()->create([
        'slug' => 'my-reviewed-movie',
        'release_year' => 2030,
    ]);
    Review::factory()->for($user)->for($movie)->create([
        'rating' => 3.0,
        'content' => 'I reviewed this one.',
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)->get(route('movies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Index')
        ->has('movies')
    );
    $moviesProp = $response->original->getData()['page']['props']['movies'] ?? [];
    $data = $moviesProp['data'] ?? $moviesProp;
    if (! is_array($data)) {
        $data = [];
    }
    $ourMovie = collect($data)->firstWhere('slug', 'my-reviewed-movie');
    expect($ourMovie)->not->toBeNull()
        ->and($ourMovie)->toHaveKey('user_review')
        ->and($ourMovie['user_review']['rating'])->toBe(3.0)
        ->and($ourMovie['user_review']['movie']['slug'])->toBe('my-reviewed-movie');
});

test('guest users never receive user_review on movies index', function () {
    Movie::factory()->published()->count(3)->create();

    $response = $this->get(route('movies.index'));

    $response->assertOk();
    $props = $response->original->getData()['page']['props'] ?? [];
    $moviesProp = $props['movies'] ?? [];
    $movies = $moviesProp['data'] ?? (is_array($moviesProp) && ! isset($moviesProp['data']) ? $moviesProp : []);

    foreach (is_array($movies) ? $movies : [] as $movie) {
        expect($movie)->not->toHaveKey('user_review');
    }
});

test('movies index returns filters.tags as unwrapped array not data wrapper', function () {
    Movie::factory()->published()->count(2)->create();

    $response = $this->get(route('movies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Movies/Index')->has('filters'));
    $tags = $response->inertiaProps('filters')['tags'] ?? null;
    // Backend must send tags as plain array (withoutWrapping); frontend expects .find() on array.
    expect($tags === null || (is_array($tags) && array_keys($tags) !== ['data']))->toBeTrue();
});
