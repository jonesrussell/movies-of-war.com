<?php

return [
    'api_key' => env('TMDB_API_KEY'),
    'base_url' => 'https://api.themoviedb.org/3',
    'image_base_url' => 'https://image.tmdb.org/t/p',

    'refresh_cadence_days' => [
        'popular' => 7,
        'normal' => 30,
        'obscure' => 90,
    ],
    'refresh_popular_vote_count' => 10_000,
    'refresh_obscure_vote_count' => 100,
    'refresh_max_age_days' => 120,

    'refresh_people_max_age_days' => 180,

    'import' => [
        'discovery_strategies' => [
            [
                'key' => 'war_genre',
                'type' => 'genre',
                'genre_id' => 10752,
            ],
            [
                'key' => 'wwii',
                'type' => 'keywords',
                'names' => ['world war ii', 'ww2', 'second world war'],
            ],
            [
                'key' => 'vietnam',
                'type' => 'keywords',
                'names' => ['vietnam war', 'vietnam'],
            ],
            [
                'key' => 'popular',
                'type' => 'popular',
            ],
            [
                'key' => 'trending_day',
                'type' => 'trending',
                'window' => 'day',
            ],
            [
                'key' => 'trending_week',
                'type' => 'trending',
                'window' => 'week',
            ],
        ],
        'min_vote_count' => 100,
        'min_vote_average' => null,
        'relevance_keywords' => ['war', 'battle', 'soldier', 'army', 'wwii', 'ww2', 'vietnam', 'combat', 'military'],
        'relevance_min_score' => null,
        'relevance_top_percent' => null,
        'gap_filling_enabled' => true,
        'require_war_genre_for_list_strategies' => true,
        'strategy_bucket_map' => [
            'war_genre' => null,
            'wwii' => 'wwii',
            'vietnam' => 'vietnam-war',
            'popular' => null,
            'trending_day' => null,
            'trending_week' => null,
        ],
        'max_ids_per_strategy' => 50,
        'keyword_cache_ttl_seconds' => 86400,
        'changes_days_back' => 1,
    ],
];
