<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\WarArticle;
use JonesRussell\NorthCloud\Models\NewsSource;

test('articles index page shows published articles', function () {
    $source = NewsSource::create(['name' => 'Test Source', 'slug' => 'test-src', 'url' => 'https://example.com']);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Published Article',
        'slug' => 'published-article',
        'url' => 'https://example.com/published',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Draft Article',
        'slug' => 'draft-article',
        'url' => 'https://example.com/draft',
        'content' => 'Content.',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $response = $this->get(route('articles.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Articles/Index')
        ->has('articles.data', 1)
        ->where('articles.data.0.title', 'Published Article')
    );
});

test('articles index page supports search', function () {
    $source = NewsSource::create(['name' => 'Source', 'slug' => 'source', 'url' => 'https://example.com']);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Battle of the Bulge Analysis',
        'slug' => 'battle-bulge',
        'url' => 'https://example.com/battle',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Film Review Roundup',
        'slug' => 'film-review',
        'url' => 'https://example.com/review',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $response = $this->get(route('articles.index', ['search' => 'Bulge']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('articles.data', 1)
        ->where('articles.data.0.slug', 'battle-bulge')
    );
});

test('article show page loads published article with associated movie', function () {
    $source = NewsSource::create(['name' => 'Source', 'slug' => 'source-show', 'url' => 'https://example.com']);
    $movie = Movie::factory()->published()->create(['title' => 'Saving Private Ryan']);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Analysis of Saving Private Ryan',
        'slug' => 'analysis-spr',
        'url' => 'https://example.com/spr',
        'content' => '<p>Deep dive content.</p>',
        'status' => 'published',
        'published_at' => now(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    $response = $this->get(route('articles.show', 'analysis-spr'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Articles/Show')
        ->where('article.title', 'Analysis of Saving Private Ryan')
        ->where('article.articleable.title', 'Saving Private Ryan')
    );
});

test('article show page returns 404 for draft articles', function () {
    $source = NewsSource::create(['name' => 'Source', 'slug' => 'source-draft', 'url' => 'https://example.com']);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Draft Article',
        'slug' => 'draft-article-hidden',
        'url' => 'https://example.com/draft',
        'content' => 'Content.',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $response = $this->get(route('articles.show', 'draft-article-hidden'));

    $response->assertNotFound();
});
