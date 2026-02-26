<?php

use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

test('queue index requires authentication', function () {
    $this->get(route('dashboard.featured-queue'))
        ->assertRedirect(route('login'));
});

test('queue index requires admin', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard.featured-queue'))
        ->assertForbidden();
});

test('queue index shows queued entries grouped by slot', function () {
    $movie = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create([
        'movie_id' => $movie->id,
        'slot' => 'hero',
        'position' => 1,
    ]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-queue'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/FeaturedSlots/Queue')
            ->has('heroQueue')
            ->has('pickOfWeekQueue')
        );
});

test('queue store requires authentication', function () {
    $movie = Movie::factory()->published()->create();

    $this->post(route('dashboard.featured-queue.store'), [
        'movie_id' => $movie->id,
        'slot' => 'hero',
        'position' => 1,
    ])->assertRedirect(route('login'));
});

test('queue store requires admin', function () {
    $movie = Movie::factory()->published()->create();

    $this->actingAs($this->user)
        ->post(route('dashboard.featured-queue.store'), [
            'movie_id' => $movie->id,
            'slot' => 'hero',
            'position' => 1,
        ])
        ->assertForbidden();
});

test('queue store adds manual entry and shifts positions', function () {
    $existing = Movie::factory()->published()->create();
    FeaturedSlotQueue::factory()->create([
        'movie_id' => $existing->id,
        'slot' => 'hero',
        'position' => 1,
        'scheduled_for' => now()->next('Sunday'),
    ]);

    $newMovie = Movie::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.store'), [
            'movie_id' => $newMovie->id,
            'slot' => 'hero',
            'position' => 1,
        ])
        ->assertRedirect();

    $inserted = FeaturedSlotQueue::where('movie_id', $newMovie->id)->first();
    expect($inserted->position)->toBe(1)
        ->and($inserted->selection_method->value)->toBe('manual');

    $shifted = FeaturedSlotQueue::where('movie_id', $existing->id)->first();
    expect($shifted->position)->toBe(2);
});

test('queue store validates published movies only', function () {
    $draft = Movie::factory()->draft()->create();

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.store'), [
            'movie_id' => $draft->id,
            'slot' => 'hero',
            'position' => 1,
        ])
        ->assertSessionHasErrors('movie_id');
});

test('queue store rejects invalid slot values', function () {
    $movie = Movie::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.store'), [
            'movie_id' => $movie->id,
            'slot' => 'invalid_slot',
            'position' => 1,
        ])
        ->assertSessionHasErrors('slot');
});

test('queue store rejects position less than 1', function () {
    $movie = Movie::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.store'), [
            'movie_id' => $movie->id,
            'slot' => 'hero',
            'position' => 0,
        ])
        ->assertSessionHasErrors('position');
});

test('queue store rejects missing required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.store'), [])
        ->assertSessionHasErrors(['movie_id', 'slot', 'position']);
});

test('queue destroy requires authentication', function () {
    $entry = FeaturedSlotQueue::factory()->create();

    $this->delete(route('dashboard.featured-queue.destroy', $entry))
        ->assertRedirect(route('login'));
});

test('queue destroy requires admin', function () {
    $entry = FeaturedSlotQueue::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('dashboard.featured-queue.destroy', $entry))
        ->assertForbidden();
});

test('queue destroy removes entry and reindexes', function () {
    $movie1 = Movie::factory()->published()->create();
    $movie2 = Movie::factory()->published()->create();

    $entry1 = FeaturedSlotQueue::factory()->create(['movie_id' => $movie1->id, 'slot' => 'hero', 'position' => 1]);
    FeaturedSlotQueue::factory()->create(['movie_id' => $movie2->id, 'slot' => 'hero', 'position' => 2]);

    $this->actingAs($this->admin)
        ->delete(route('dashboard.featured-queue.destroy', $entry1))
        ->assertRedirect();

    expect(FeaturedSlotQueue::find($entry1->id))->toBeNull();

    $remaining = FeaturedSlotQueue::where('movie_id', $movie2->id)->first();
    expect($remaining->position)->toBe(1);
});

test('queue refill requires authentication', function () {
    $this->post(route('dashboard.featured-queue.refill'))
        ->assertRedirect(route('login'));
});

test('queue refill requires admin', function () {
    $this->actingAs($this->user)
        ->post(route('dashboard.featured-queue.refill'))
        ->assertForbidden();
});

test('queue refill triggers service fillQueue', function () {
    Movie::factory()->published()->count(10)->create(['tmdb_vote_average' => 7.0]);

    $this->actingAs($this->admin)
        ->post(route('dashboard.featured-queue.refill'))
        ->assertRedirect();

    expect(FeaturedSlotQueue::count())->toBeGreaterThan(0);
});
