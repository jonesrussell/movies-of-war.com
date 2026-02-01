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
];
