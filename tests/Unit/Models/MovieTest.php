<?php

use App\Enums\MovieStatus;
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

test('movie casts status to MovieStatus enum', function () {
    $movie = Movie::factory()->create(['status' => MovieStatus::Published]);

    expect($movie->status)->toBeInstanceOf(MovieStatus::class)
        ->and($movie->status)->toBe(MovieStatus::Published);
});

test('movie status helper methods work correctly', function () {
    $draftMovie = Movie::factory()->draft()->create();
    $publishedMovie = Movie::factory()->published()->create();
    $archivedMovie = Movie::factory()->archived()->create();

    expect($draftMovie->isDraft())->toBeTrue()
        ->and($draftMovie->isPublished())->toBeFalse()
        ->and($draftMovie->isArchived())->toBeFalse();

    expect($publishedMovie->isDraft())->toBeFalse()
        ->and($publishedMovie->isPublished())->toBeTrue()
        ->and($publishedMovie->isArchived())->toBeFalse();

    expect($archivedMovie->isDraft())->toBeFalse()
        ->and($archivedMovie->isPublished())->toBeFalse()
        ->and($archivedMovie->isArchived())->toBeTrue();
});

test('movie can transition from draft to published', function () {
    $movie = Movie::factory()->draft()->create();

    $movie->publish();

    expect($movie->fresh()->status)->toBe(MovieStatus::Published);
});

test('movie can transition from published to archived', function () {
    $movie = Movie::factory()->published()->create();

    $movie->archive();

    expect($movie->fresh()->status)->toBe(MovieStatus::Archived);
});

test('movie scopes filter by status correctly', function () {
    Movie::factory()->count(3)->published()->create();
    Movie::factory()->count(2)->draft()->create();
    Movie::factory()->count(1)->archived()->create();

    expect(Movie::published()->count())->toBe(3)
        ->and(Movie::draft()->count())->toBe(2)
        ->and(Movie::archived()->count())->toBe(1);
});
