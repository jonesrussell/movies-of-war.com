<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Redis connection and channel for article ingestion.
    |
    */
    'redis' => [
        'connection' => env('REDIS_ARTICLES_CONNECTION', 'default'),
        'channel' => env('REDIS_ARTICLES_CHANNEL', 'articles:default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Filtering
    |--------------------------------------------------------------------------
    |
    | Filter articles based on quality score if enabled.
    |
    */
    'quality' => [
        'min_score' => env('ARTICLES_MIN_QUALITY_SCORE', 0),
        'enabled' => env('ARTICLES_QUALITY_FILTER', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which models the package should use. Override these in your
    | app's published config to use your custom models.
    |
    */
    'models' => [
        'article' => \JonesRussell\LaravelRedisArticles\Models\Article::class,
        'news_source' => \JonesRussell\LaravelRedisArticles\Models\NewsSource::class,
        'tag' => \JonesRussell\LaravelRedisArticles\Models\Tag::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how articles are processed after being received.
    |
    */
    'processing' => [
        'processor' => \JonesRussell\LaravelRedisArticles\Services\ArticleIngestionService::class,
        'sync' => env('ARTICLES_PROCESS_SYNC', true), // Sync vs queued processing
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Configuration
    |--------------------------------------------------------------------------
    |
    | Configure content sanitization and processing rules.
    |
    */
    'content' => [
        'allowed_tags' => ['p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        'max_excerpt_length' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tag Configuration
    |--------------------------------------------------------------------------
    |
    | Configure tag behavior and types.
    |
    */
    'tags' => [
        'default_type' => 'article_category',
        'auto_create' => true,
    ],
];
