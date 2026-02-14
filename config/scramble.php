<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
    |--------------------------------------------------------------------------
    | API path
    |--------------------------------------------------------------------------
    |
    | Prefix used to match API routes. Scramble documents routes starting with this path.
    |
    */
    'api_path' => 'api',

    /*
    |--------------------------------------------------------------------------
    | API domain
    |--------------------------------------------------------------------------
    |
    | Your API domain. By default, app domain is used. Part of the default routes matcher.
    |
    */
    'api_domain' => null,

    'info' => [
        'version' => env('API_VERSION', '0.0.1'),
        'title' => env('API_TITLE', 'Intent-1 API'),
        'description' => env('API_DESCRIPTION', 'API para la administración de edificios y conjuntos residenciales (Intent-1).'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Servers
    |--------------------------------------------------------------------------
    |
    | List of API servers for the OpenAPI document. When null, server URL is
    | built from api_path and api_domain. Example: ['Live' => 'api', 'Prod' => 'https://api.example.com']
    |
    */
    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
