<?php

use App\Models\FeaturedSlotHistory;
use App\Models\Movie;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

test('history index requires admin', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard.featured-history'))
        ->assertForbidden();
});

test('history index returns paginated history', function () {
    $movie = Movie::factory()->published()->create();
    FeaturedSlotHistory::factory()->create(['movie_id' => $movie->id]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/FeaturedSlots/History')
            ->has('history.data', 1)
            ->has('history.meta')
        );
});

test('history index filters by slot type', function () {
    FeaturedSlotHistory::factory()->create(['slot' => 'hero']);
    FeaturedSlotHistory::factory()->create(['slot' => 'pick_of_week']);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history', ['slot' => 'hero']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('history.data', 1)
            ->where('history.data.0.slot', 'hero')
        );
});

test('history index filters by selection method', function () {
    FeaturedSlotHistory::factory()->create(['selection_method' => 'auto']);
    FeaturedSlotHistory::factory()->create(['selection_method' => 'manual']);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history', ['method' => 'manual']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('history.data', 1)
            ->where('history.data.0.selection_method', 'manual')
        );
});

test('history index sorts by started_at desc by default', function () {
    FeaturedSlotHistory::factory()->create(['started_at' => now()->subWeeks(2)]);
    FeaturedSlotHistory::factory()->create(['started_at' => now()->subWeek()]);

    $this->actingAs($this->admin)
        ->get(route('dashboard.featured-history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('history.data', 2)
        );
});
