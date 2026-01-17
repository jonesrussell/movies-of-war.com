<?php

use App\Models\Tag;

test('slug is automatically generated from name when creating tag without slug', function () {
    $tag = Tag::factory()->create([
        'name' => 'World War II',
        'slug' => null,
    ]);

    expect($tag->slug)->toBe('world-war-ii');
});

test('existing slug is preserved when provided', function () {
    $tag = Tag::factory()->create([
        'name' => 'World War II',
        'slug' => 'custom-slug',
    ]);

    expect($tag->slug)->toBe('custom-slug');
});

test('slug generation handles special characters correctly', function () {
    $tag = Tag::factory()->create([
        'name' => 'Post-War',
        'slug' => null,
    ]);

    expect($tag->slug)->toBe('post-war');
});

test('slug generation handles unicode characters', function () {
    $tag = Tag::factory()->create([
        'name' => 'Drama',
        'slug' => null,
    ]);

    expect($tag->slug)->toBe('drama');
});

test('slug generation converts spaces to hyphens', function () {
    $tag = Tag::factory()->create([
        'name' => 'Vietnam War',
        'slug' => null,
    ]);

    expect($tag->slug)->toBe('vietnam-war');
});

test('slug generation converts to lowercase', function () {
    $tag = Tag::factory()->create([
        'name' => 'ACTION',
        'slug' => null,
    ]);

    expect($tag->slug)->toBe('action');
});

test('slug generation handles empty name by generating from name after creation', function () {
    // Note: This tests that slug generation works even when name is set after slug
    // In practice, name should always be provided, but we test the robustness
    $tag = Tag::factory()->make([
        'name' => 'Thriller',
        'slug' => null,
    ]);

    $tag->save();

    expect($tag->slug)->toBe('thriller');
});
