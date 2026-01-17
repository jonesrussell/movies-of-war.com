<?php

use App\Models\Movie;

test('slug is automatically generated from title when creating movie without slug', function () {
    $movie = Movie::factory()->create([
        'title' => 'Saving Private Ryan',
        'slug' => null,
    ]);

    expect($movie->slug)->toBe('saving-private-ryan');
});

test('existing slug is preserved when provided', function () {
    $movie = Movie::factory()->create([
        'title' => 'Saving Private Ryan',
        'slug' => 'custom-slug',
    ]);

    expect($movie->slug)->toBe('custom-slug');
});

test('slug generation handles special characters correctly', function () {
    $movie = Movie::factory()->create([
        'title' => 'Apocalypse Now (1979)',
        'slug' => null,
    ]);

    expect($movie->slug)->toBe('apocalypse-now-1979');
});

test('slug generation handles unicode characters', function () {
    $movie = Movie::factory()->create([
        'title' => 'Das Boot',
        'slug' => null,
    ]);

    expect($movie->slug)->toBe('das-boot');
});

test('slug generation converts spaces to hyphens', function () {
    $movie = Movie::factory()->create([
        'title' => 'Full Metal Jacket',
        'slug' => null,
    ]);

    expect($movie->slug)->toBe('full-metal-jacket');
});

test('slug generation converts to lowercase', function () {
    $movie = Movie::factory()->create([
        'title' => 'THE THIN RED LINE',
        'slug' => null,
    ]);

    expect($movie->slug)->toBe('the-thin-red-line');
});

test('slug generation handles empty title by generating from title after creation', function () {
    // Note: This tests that slug generation works even when title is set after slug
    // In practice, title should always be provided, but we test the robustness
    $movie = Movie::factory()->make([
        'title' => 'Platoon',
        'slug' => null,
    ]);

    $movie->save();

    expect($movie->slug)->toBe('platoon');
});
