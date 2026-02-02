<?php

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

test('guests cannot access admin routes', function () {
    $response = $this->get('/admin');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

test('regular users cannot access admin routes', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/dashboard/movies');

    $response->assertForbidden();
});

test('admin users can access admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

test('admin users can access admin movies page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get('/dashboard/movies');

    $response->assertOk();
});

test('admin users can access admin featured slots page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get('/dashboard/featured-slots');

    $response->assertOk();
});

test('admin users can access admin reviews page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get('/dashboard/reviews');

    $response->assertOk();
});

test('regular users cannot access admin reviews page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/dashboard/reviews');

    $response->assertForbidden();
});

test('admin can delete any review', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $movie = Movie::factory()->create();
    $review = Review::factory()->create(['movie_id' => $movie->id]);

    $response = $this->actingAs($admin)->delete("/dashboard/reviews/{$review->id}");

    $response->assertRedirect();
    expect(Review::find($review->id))->toBeNull();
});

test('admin can toggle review published status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $movie = Movie::factory()->create();
    $review = Review::factory()->create(['movie_id' => $movie->id, 'is_published' => true]);

    $response = $this->actingAs($admin)->post("/dashboard/reviews/{$review->id}/toggle-published");

    $response->assertRedirect();
    expect($review->fresh()->is_published)->toBeFalse();
});

test('regular user cannot delete review via admin route', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $movie = Movie::factory()->create();
    $review = Review::factory()->create(['movie_id' => $movie->id]);

    $response = $this->actingAs($user)->delete("/dashboard/reviews/{$review->id}");

    $response->assertForbidden();
    expect(Review::find($review->id))->not->toBeNull();
});

test('regular users cannot create movies', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/movies/create');

    $response->assertForbidden();
});

test('regular users cannot edit movies', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $movie = \App\Models\Movie::factory()->create();

    $response = $this->actingAs($user)->get("/movies/{$movie->id}/edit");

    $response->assertForbidden();
});
