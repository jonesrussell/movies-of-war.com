<?php

declare(strict_types=1);

use App\Data\CuratorReview;

it('creates a curator review from valid data', function () {
    $review = new CuratorReview(
        title: 'Gallipoli',
        year: 1981,
        rating: 3.0,
        director: 'Peter Weir',
        starring: ['Mark Lee', 'Mel Gibson'],
        runtime: 110,
        slug: 'gallipoli-1981',
        hasSpoilers: false,
        contentHtml: '<p>A great film.</p>',
        contentExcerpt: 'A great film.',
    );

    expect($review->title)->toBe('Gallipoli');
    expect($review->rating)->toBe(3.0);
    expect($review->slug)->toBe('gallipoli-1981');
    expect($review->starring)->toBe(['Mark Lee', 'Mel Gibson']);
    expect($review->contentHtml)->toContain('<p>');
});

it('converts to array for Inertia props', function () {
    $review = new CuratorReview(
        title: 'Gallipoli',
        year: 1981,
        rating: 3.0,
        director: 'Peter Weir',
        starring: ['Mark Lee', 'Mel Gibson'],
        runtime: 110,
        slug: 'gallipoli-1981',
        hasSpoilers: false,
        contentHtml: '<p>Review body.</p>',
        contentExcerpt: 'Review body.',
    );

    $array = $review->toArray();

    expect($array)->toHaveKeys([
        'title', 'year', 'rating', 'director', 'starring',
        'runtime', 'slug', 'has_spoilers', 'content_html', 'content_excerpt',
    ]);
    expect($array['has_spoilers'])->toBeFalse();
    expect($array['content_html'])->toBe('<p>Review body.</p>');
});
