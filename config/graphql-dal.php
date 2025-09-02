<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GraphQL DAL Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the GraphQL DAL package.
    | You can customize these settings to match your application's needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The default database connection to use for the DAL operations.
    |
    */
    'default_connection' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for database transaction management.
    |
    */
    'transactions' => [
        'auto_commit' => true,
        'max_retries' => 3,
        'retry_delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for repository pattern implementation.
    |
    */
    'repositories' => [
        'cache_enabled' => env('GRAPHQL_DAL_CACHE_ENABLED', false),
        'cache_ttl' => env('GRAPHQL_DAL_CACHE_TTL', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | GraphQL Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for GraphQL integration.
    |
    */
    'graphql' => [
        'schema_path' => base_path('graphql/schema.graphql'),
        'enable_introspection' => env('GRAPHQL_DAL_INTROSPECTION', true),
        'max_query_depth' => env('GRAPHQL_DAL_MAX_QUERY_DEPTH', 10),
        'max_query_complexity' => env('GRAPHQL_DAL_MAX_QUERY_COMPLEXITY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for logging DAL operations.
    |
    */
    'logging' => [
        'enabled' => env('GRAPHQL_DAL_LOGGING_ENABLED', false),
        'level' => env('GRAPHQL_DAL_LOG_LEVEL', 'info'),
        'channels' => [
            'database' => env('GRAPHQL_DAL_DB_LOG_CHANNEL', 'daily'),
            'graphql' => env('GRAPHQL_DAL_GRAPHQL_LOG_CHANNEL', 'daily'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization.
    |
    */
    'performance' => [
        'enable_query_caching' => env('GRAPHQL_DAL_QUERY_CACHE', false),
        'enable_connection_pooling' => env('GRAPHQL_DAL_CONNECTION_POOLING', true),
        'max_connections' => env('GRAPHQL_DAL_MAX_CONNECTIONS', 10),
    ],
];
