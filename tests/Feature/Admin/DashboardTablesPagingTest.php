<?php

declare(strict_types=1);

use App\Enums\MovieStatus;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

test('dashboard movies index accepts sort per_page and returns pagination meta', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Movie::factory()->create(['title' => 'Alpha', 'status' => MovieStatus::Published]);
    Movie::factory()->create(['title' => 'Beta', 'status' => MovieStatus::Published]);

    $response = $this->actingAs($admin)->get('/dashboard/movies?sort=title_asc&per_page=10');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Movies/Index')
        ->has('movies')
        ->has('movies.data')
        ->has('movies.meta')
        ->where('movies.meta.per_page', 10)
        ->where('movies.data.0.title', 'Alpha')
        ->where('queryParams.sort', 'title_asc')
        ->where('queryParams.per_page', 10)
    );
});

test('dashboard movies index preserves search in pagination links', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Movie::factory()->count(25)->create([
        'status' => MovieStatus::Published,
        'title' => fn () => 'Test Movie '.fake()->unique()->numberBetween(1, 100),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard/movies?search=Test&per_page=10');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('movies.meta.links')
    );
    $props = $response->original->getData()['page']['props'];
    $links = $props['movies']['meta']['links'] ?? [];
    $nextLink = collect($links)->first(fn ($l) => ($l['url'] ?? null) !== null && str_contains((string) $l['url'], 'page=2'));
    expect($nextLink)->not->toBeNull();
    expect($nextLink['url'])->toContain('search=Test');
});

test('dashboard movies index filters by status draft', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Movie::factory()->draft()->create(['title' => 'Draft One']);
    Movie::factory()->published()->create(['title' => 'Published One']);

    $response = $this->actingAs($admin)->get('/dashboard/movies?status=draft');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('queryParams.status', 'draft')
        ->has('movies.data')
        ->where('movies.data.0.title', 'Draft One')
        ->has('movies.data', 1)
    );
});

test('dashboard movies archived accepts sort and per_page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Movie::factory()->archived()->count(2)->create();

    $response = $this->actingAs($admin)->get('/dashboard/movies/archived?sort=title_asc&per_page=10');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Movies/Archived')
        ->has('movies.meta')
        ->where('queryParams.sort', 'title_asc')
        ->where('queryParams.per_page', 10)
    );
});

test('dashboard reviews index accepts sort and per_page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $movie1 = Movie::factory()->published()->create();
    $movie2 = Movie::factory()->published()->create();
    Review::factory()->for($admin)->for($movie1)->create();
    Review::factory()->for($admin)->for($movie2)->create();

    $response = $this->actingAs($admin)->get('/dashboard/reviews?sort=created_at_asc&per_page=10');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Reviews/Index')
        ->has('reviews.data')
        ->has('reviews.meta')
        ->where('queryParams.sort', 'created_at_asc')
        ->where('queryParams.per_page', 10)
    );
});

test('dashboard reviews index filters by unpublished', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $movie = Movie::factory()->published()->create();
    Review::factory()->for($admin)->for($movie)->create(['is_published' => false]);

    $response = $this->actingAs($admin)->get('/dashboard/reviews?published=0');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('queryParams.published', '0')
        ->has('reviews.data')
        ->where('reviews.data.0.is_published', false)
    );
});

test('dashboard reviews index filters by published', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $movie1 = Movie::factory()->published()->create();
    $movie2 = Movie::factory()->published()->create();
    Review::factory()->for($admin)->for($movie1)->create(['is_published' => true]);
    Review::factory()->for($admin)->for($movie2)->create(['is_published' => false]);

    $response = $this->actingAs($admin)->get('/dashboard/reviews?published=1');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('queryParams.published', '1')
        ->has('reviews.data', 1)
        ->where('reviews.data.0.is_published', true)
    );
});

test('dashboard featured slots index accepts sort and per_page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get('/dashboard/featured-slots?sort=movie_title_asc&per_page=10');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/FeaturedSlots/Index')
        ->has('slots.data')
        ->has('slots.meta')
        ->where('queryParams.sort', 'movie_title_asc')
        ->where('queryParams.per_page', 10)
    );
});

test('dashboard tmdb imports accepts sort and per_page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Movie::factory()->draft()->count(2)->create();

    $response = $this->actingAs($admin)->get('/dashboard/tmdb/imports?sort=title_asc&per_page=10');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/TmdbImports')
        ->has('tmdbDrafts.data')
        ->has('tmdbDrafts.meta')
        ->where('queryParams.sort', 'title_asc')
        ->where('queryParams.per_page', 10)
    );
});
