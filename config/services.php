<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'google' => [
        'maps_key' => env('GOOGLE_MAPS_API_KEY'),
        'routes_cache_ttl' => env('GOOGLE_ROUTES_CACHE_TTL', 600),
        'routes_cache_precision' => env('GOOGLE_ROUTES_CACHE_PRECISION', 3),
        'routes_cache_prefix' => env('GOOGLE_ROUTES_CACHE_PREFIX', 'gmaps:rmx:'),
        'max_candidates' => env('GOOGLE_ROUTES_MAX_CANDIDATES', 250),
    ],
    'ors' => [
        'api_key' => env('ORS_API_KEY'),
        'cache_ttl' => env('ORS_ROUTES_CACHE_TTL', 600),
        'precision' => env('ORS_ROUTES_CACHE_PRECISION', 3),
        'cache_prefix' => env('ORS_ROUTES_CACHE_PREFIX', 'ors:rmx:'),
        'max_candidates' => env('ORS_MAX_CANDIDATES', 200),
    ],
];
