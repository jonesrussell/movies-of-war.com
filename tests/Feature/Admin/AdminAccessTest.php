<?php

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
