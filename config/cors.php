<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    | This configuration allows the frontend dev server (localhost:5173) to
    | access the API with credentials (cookies) for Sanctum authentication.
    */
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => ['*'],

    // Allow the frontend dev origin; recommend overriding via FRONTEND_URL in production/dev environments
    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'https://ri-ifba.vercel.app',
        'https://ri-ifba.netlify.app'
    ]),

    'allowed_origins_patterns' => [
        '#^https://.*\.railway\.app$#',
        '#^https://.*\.up\.railway\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Content-Disposition',
        'Content-Type',
        'Content-Length',
    ],

    'max_age' => 0,

    // Important: allow credentials so Sanctum cookies are sent/accepted
    'supports_credentials' => true,
];

