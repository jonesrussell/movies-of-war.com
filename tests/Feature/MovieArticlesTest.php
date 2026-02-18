<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\WarArticle;
use JonesRussell\NorthCloud\Models\NewsSource;

test('movie has morphMany articles relationship', function () {
    $movie = Movie::factory()->published()->create();
    $source = NewsSource::create(['name' => 'Test Source', 'slug' => 'test-source', 'url' => 'https://example.com']);

    $article = WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article about the movie',
        'slug' => 'article-about-the-movie',
        'url' => 'https://example.com/article',
        'content' => 'Content here.',
        'status' => 'published',
        'published_at' => now(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    expect($movie->articles)->toHaveCount(1);
    expect($movie->articles->first()->id)->toBe($article->id);
});

test('article articleable resolves to movie', function () {
    $movie = Movie::factory()->published()->create();
    $source = NewsSource::create(['name' => 'Test Source', 'slug' => 'test-source-2', 'url' => 'https://example.com']);

    $article = WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article about the movie',
        'slug' => 'article-about-movie',
        'url' => 'https://example.com/article-2',
        'content' => 'Content here.',
        'status' => 'published',
        'published_at' => now(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    expect($article->articleable)->toBeInstanceOf(Movie::class);
    expect($article->articleable->id)->toBe($movie->id);
});

test('movie articles only returns published articles', function () {
    $movie = Movie::factory()->published()->create();
    $source = NewsSource::create(['name' => 'Test Source', 'slug' => 'test-source-3', 'url' => 'https://example.com']);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Published article',
        'slug' => 'published-article',
        'url' => 'https://example.com/published',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Draft article',
        'slug' => 'draft-article',
        'url' => 'https://example.com/draft',
        'content' => 'Content.',
        'status' => 'draft',
        'published_at' => null,
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    expect($movie->articles()->published()->count())->toBe(1);
});
