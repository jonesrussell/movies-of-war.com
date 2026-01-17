<?php

// You can find the keys here : https://developer.twitter.com/en/portal/projects-and-apps

return [
    'debug' => env('APP_DEBUG', false),

    'api_url' => 'api.twitter.com',
    'upload_url' => 'upload.twitter.com',
    'api_version' => env('TWITTER_API_VERSION', '2'),

    'consumer_key' => env('TWITTER_CONSUMER_KEY', env('TWITTER_API_KEY')),
    'consumer_secret' => env('TWITTER_CONSUMER_SECRET', env('TWITTER_API_SECRET')),
    'access_token' => env('TWITTER_ACCESS_TOKEN'),
    'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),

    // OAuth 2.0 credentials
    'client_id' => env('TWITTER_CLIENT_ID', env('X_CLIENT_SECRET_ID')),
    'client_secret' => env('TWITTER_CLIENT_SECRET', env('X_CLIENT_SECRET')),
    // Bearer token from developer portal (OAuth 2.0)
    'oauth2_access_token' => env('TWITTER_OAUTH2_ACCESS_TOKEN', env('TWITTER_BEARER_TOKEN')),

    'authenticate_url' => 'https://api.twitter.com/oauth/authenticate',
    'access_token_url' => 'https://api.twitter.com/oauth/access_token',
    'request_token_url' => 'https://api.twitter.com/oauth/request_token',
];
