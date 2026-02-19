<?php

return [
    'migrations' => [
        'enabled' => false,
    ],

    'redis' => [
        'connection' => env('NORTHCLOUD_REDIS_CONNECTION', env('REDIS_ARTICLES_CONNECTION', 'default')),
        'channels' => [env('NORTHCLOUD_CHANNEL', env('REDIS_ARTICLES_CHANNEL', 'articles:war'))],
    ],

    'quality' => [
        'min_score' => (int) env('NORTHCLOUD_MIN_QUALITY_SCORE', 70),
        'enabled' => (bool) env('NORTHCLOUD_QUALITY_FILTER', true),
    ],

    'models' => [
        'article' => \App\Models\WarArticle::class,
        'news_source' => \JonesRussell\NorthCloud\Models\NewsSource::class,
        'tag' => \JonesRussell\NorthCloud\Models\Tag::class,
    ],

    'processors' => [
        \JonesRussell\NorthCloud\Processing\DefaultArticleProcessor::class,
    ],

    'processing' => [
        'sync' => (bool) env('NORTHCLOUD_PROCESS_SYNC', true),
    ],

    'content' => [
        'allowed_tags' => ['p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    ],

    'tags' => [
        'default_type' => 'theme',
        'auto_create' => true,
        'allowed' => [],
    ],

    'linking' => [
        'enabled' => true,
        'threshold' => 0.3,
        'weights' => [
            'title_match' => 0.5,
            'tag_overlap' => 0.3,
            'metadata_match' => 0.2,
        ],
        'min_keyword_length' => 3,
    ],

    'navigation' => [
        'enabled' => true,
        'items' => [
            ['title' => 'Movies', 'route' => 'dashboard.movies', 'icon' => 'Film'],
            ['title' => 'TMDB Imports', 'route' => 'dashboard.tmdb.imports', 'icon' => 'Download'],
            ['title' => 'Featured', 'route' => 'dashboard.featured-slots', 'icon' => 'Star'],
            ['title' => 'Reviews', 'route' => 'dashboard.reviews', 'icon' => 'MessageSquare'],
        ],
    ],

    'articleable' => [
        'enabled' => true,
        'models' => [
            \App\Models\Movie::class => [
                'label' => 'Movie',
                'display' => 'title',
                'search' => ['title'],
            ],
        ],
    ],
];
