<?php



    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // return [
    //     'paths' => ['api/*', 'sanctum/csrf-cookie'],
    //     'allowed_methods' => ['*'],
    //     'allowed_origins' => ['http://localhost:5173',
    //                           'https://onebutton.in'], 

    //     'allowed_origins_patterns' => [],
    //     'allowed_headers' => ['*'],
    //     'supports_credentials' => true,
    // ];
    




    return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',   // dev
        'https://onebutton.in',    // production
        'https://www.onebutton.in' // production www
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
