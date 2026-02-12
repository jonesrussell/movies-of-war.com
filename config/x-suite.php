<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Twitter / X API Credentials
    |--------------------------------------------------------------------------
    */

    'twitter' => [
        'debug' => env('APP_DEBUG', false),
        'api_url' => 'api.twitter.com',
        'upload_url' => 'upload.twitter.com',
        'api_version' => env('TWITTER_API_VERSION', '2'),

        'consumer_key' => env('TWITTER_CONSUMER_KEY', env('TWITTER_API_KEY')),
        'consumer_secret' => env('TWITTER_CONSUMER_SECRET', env('TWITTER_API_SECRET')),
        'access_token' => env('TWITTER_ACCESS_TOKEN'),
        'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),

        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'oauth2_access_token' => env('TWITTER_OAUTH2_ACCESS_TOKEN', env('TWITTER_BEARER_TOKEN')),

        'user_id' => env('TWITTER_USER_ID'),

        'authenticate_url' => 'https://api.twitter.com/oauth/authenticate',
        'access_token_url' => 'https://api.twitter.com/oauth/access_token',
        'request_token_url' => 'https://api.twitter.com/oauth/request_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model & Authentication
    |--------------------------------------------------------------------------
    */

    'user_model' => env('X_SUITE_USER_MODEL', 'App\\Models\\User'),
    'admin_attribute' => 'is_admin',

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */

    'route_prefix' => '',
    'route_name_prefix' => 'admin',
    'middleware' => ['web', 'auth', 'verified', 'admin'],

    /*
    |--------------------------------------------------------------------------
    | Inertia / Frontend
    |--------------------------------------------------------------------------
    */

    'inertia_page_prefix' => 'Admin',

    /*
    |--------------------------------------------------------------------------
    | Content Settings
    |--------------------------------------------------------------------------
    */

    'max_tweet_length' => 280,
    'spreadsheet_path' => env('X_POSTS_SPREADSHEET_PATH', resource_path('xslx/MoW_X_Posts_2026_CLEANED.xlsx')),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'routes' => true,
        'commands' => true,
        'scheduler' => false,
        'public_feed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Discovery Defaults
    |--------------------------------------------------------------------------
    */

    'discovery' => [
        'default_queries' => [
            '#WarMovies',
            '#WWII',
            '#VietnamWar',
            '#WarFilms',
            '#MilitaryHistory',
        ],
    ],

];
