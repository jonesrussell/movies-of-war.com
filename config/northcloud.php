<?php

return [
    'migrations' => [
        'enabled' => false,
    ],

    'redis' => [
        'connection' => env('NORTHCLOUD_REDIS_CONNECTION', env('REDIS_ARTICLES_CONNECTION', 'default')),
        'channel' => env('NORTHCLOUD_CHANNEL', env('REDIS_ARTICLES_CHANNEL', 'articles:war')),
    ],

    'quality' => [
        'min_score' => (int) env('NORTHCLOUD_MIN_QUALITY_SCORE', 70),
        'enabled' => (bool) env('NORTHCLOUD_QUALITY_FILTER', true),
    ],

    'models' => [
        'article' => \App\Models\WarArticle::class,
        'news_source' => \JonesRussell\NorthcloudLaravel\Models\NewsSource::class,
        'tag' => \JonesRussell\NorthcloudLaravel\Models\Tag::class,
    ],

    'processing' => [
        'processor' => \JonesRussell\NorthcloudLaravel\Services\ArticleIngestionService::class,
        'sync' => (bool) env('NORTHCLOUD_PROCESS_SYNC', true),
    ],

    'content' => [
        'allowed_tags' => ['p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        'max_excerpt_length' => 500,
    ],

    'tags' => [
        'default_type' => 'theme',
        'auto_create' => true,
        'allowed' => [],
    ],
];
