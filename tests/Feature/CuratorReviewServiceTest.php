<?php

declare(strict_types=1);

use App\Data\CuratorReview;
use App\Models\Movie;
use App\Services\CuratorReviewService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('returns null when no review file exists for the slug', function () {
    $service = app(CuratorReviewService::class);

    $result = $service->forMovie('nonexistent-movie');

    expect($result)->toBeNull();
});

it('parses a valid review file and returns a CuratorReview', function () {
    $reviewContent = <<<'MD'
---
title: "Test Movie"
year: 2020
rating: 3
director: "Test Director"
starring: ["Actor One", "Actor Two"]
runtime: 120
---

This is a test review.

[quote]A great quote.[/quote]
MD;

    $path = resource_path('reviews/test-movie-2020.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('test-movie-2020');

    expect($result)->toBeInstanceOf(CuratorReview::class);
    expect($result->title)->toBe('Test Movie');
    expect($result->year)->toBe(2020);
    expect($result->rating)->toBe(3.0);
    expect($result->director)->toBe('Test Director');
    expect($result->starring)->toBe(['Actor One', 'Actor Two']);
    expect($result->runtime)->toBe(120);
    expect($result->slug)->toBe('test-movie-2020');
    expect($result->hasSpoilers)->toBeFalse();
    expect($result->contentHtml)->toContain('<p>This is a test review.</p>');
    expect($result->contentHtml)->toContain('<blockquote class="pull-quote">');
    expect($result->contentExcerpt)->toContain('This is a test review.');

    @unlink($path);
});

it('uses frontmatter slug override when provided', function () {
    $reviewContent = <<<'MD'
---
title: "Custom Slug Movie"
year: 2019
rating: 2.5
slug: custom-slug
---

Review body.
MD;

    // Note: file is named after the slug override so forMovie('custom-slug') finds it
    $path = resource_path('reviews/custom-slug.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('custom-slug');

    expect($result)->toBeInstanceOf(CuratorReview::class);
    expect($result->slug)->toBe('custom-slug');
    expect($result->title)->toBe('Custom Slug Movie');

    @unlink($path);
});

it('caches the result and serves from cache on second call', function () {
    $reviewContent = <<<'MD'
---
title: "Cached Movie"
year: 2021
rating: 4
---

Cached review.
MD;

    $path = resource_path('reviews/cached-movie.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);

    $first = $service->forMovie('cached-movie');
    expect($first)->toBeInstanceOf(CuratorReview::class);

    // Delete the file — cache should still serve
    unlink($path);

    $second = $service->forMovie('cached-movie');
    expect($second)->toBeInstanceOf(CuratorReview::class);
    expect($second->title)->toBe('Cached Movie');
});

it('busts cache when file modification time changes', function () {
    $path = resource_path('reviews/bust-cache-movie.md');

    file_put_contents($path, <<<'MD'
---
title: "Original Title"
year: 2022
rating: 3
---

Original content.
MD);

    $service = app(CuratorReviewService::class);
    $first = $service->forMovie('bust-cache-movie');
    expect($first->title)->toBe('Original Title');

    // Update the file (touch to change mtime)
    sleep(1);
    file_put_contents($path, <<<'MD'
---
title: "Updated Title"
year: 2022
rating: 3.5
---

Updated content.
MD);

    $second = $service->forMovie('bust-cache-movie');
    expect($second->title)->toBe('Updated Title');

    @unlink($path);
});

it('expands film shortcodes with DB lookups', function () {
    Movie::factory()->published()->create([
        'slug' => 'referenced-film',
        'title' => 'Referenced Film',
    ]);

    $reviewContent = <<<'MD'
---
title: "Referencing Movie"
year: 2023
rating: 3
---

Similar to [film:referenced-film] in style.
MD;

    $path = resource_path('reviews/referencing-movie.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('referencing-movie');

    expect($result->contentHtml)->toContain('Referenced Film</a>');
    expect($result->contentHtml)->toContain('href="/movies/referenced-film"');

    @unlink($path);
});

it('finds review via base-slug fallback when movie slug has year suffix', function () {
    $service = app(CuratorReviewService::class);

    $result = $service->forMovie('gallipoli-1981');

    expect($result)->toBeInstanceOf(CuratorReview::class);
    expect($result->title)->toBe('Gallipoli');
    expect($result->slug)->toBe('gallipoli-1981');
    expect($result->year)->toBe(1981);
    expect($result->director)->toBe('Peter Weir');
});

it('returns null for a review with missing required frontmatter', function () {
    $reviewContent = <<<'MD'
---
year: 2020
---

No title provided.
MD;

    $path = resource_path('reviews/bad-frontmatter.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('bad-frontmatter');

    expect($result)->toBeNull();

    @unlink($path);
});
