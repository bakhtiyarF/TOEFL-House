<?php

/**
 * CORS Configuration for Production
 * 
 * Per 13_INFRASTRUCTURE_AND_DEPLOYMENT.md:
 * - Strict CORS policy for production
 * - Allow only trusted frontend domains
 * - Restrict methods and headers
 * - Enable credentials for Sanctum authentication
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You may adjust these settings as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => env('APP_ENV') === 'production' 
        ? [
            'https://toeflhouse.af',
            'https://www.toeflhouse.af',
            'https://app.toeflhouse.af',
        ]
        : ['http://localhost:5173', 'http://localhost:3000', 'http://127.0.0.1:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-API-Version',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-API-Version',
        'Retry-After',
    ],

    'max_age' => 3600, // Cache preflight requests for 1 hour

    'supports_credentials' => true, // Required for Sanctum cookie authentication
];
