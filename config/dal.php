<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    |
    | The default database connection to use for the DAL package.
    | This should match one of the connections defined in your database config.
    |
    */
    'default_connection' => env('DAL_DEFAULT_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Configure additional database connections specific to the DAL package.
    | These will be merged with your main database connections.
    |
    */
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Configuration
    |--------------------------------------------------------------------------
    |
    | Configure repository behavior and caching.
    |
    */
    'repositories' => [
        'cache_enabled' => env('DAL_CACHE_ENABLED', false),
        'cache_ttl' => env('DAL_CACHE_TTL', 3600), // 1 hour
        'cache_prefix' => env('DAL_CACHE_PREFIX', 'dal_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Configuration
    |--------------------------------------------------------------------------
    |
    | Configure transaction behavior and retry logic.
    |
    */
    'transactions' => [
        'retry_attempts' => env('DAL_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('DAL_RETRY_DELAY', 100), // milliseconds
        'timeout' => env('DAL_TRANSACTION_TIMEOUT', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | GraphQL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure GraphQL behavior for the DAL package.
    |
    */
    'graphql' => [
        'enabled' => env('DAL_GRAPHQL_ENABLED', true),
        'schema_path' => env('DAL_GRAPHQL_SCHEMA_PATH', 'graphql/schema.graphql'),
        'cache_schema' => env('DAL_GRAPHQL_CACHE_SCHEMA', true),
        'max_query_depth' => env('DAL_GRAPHQL_MAX_DEPTH', 10),
        'max_query_complexity' => env('DAL_GRAPHQL_MAX_COMPLEXITY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configure model behavior and relationships.
    |
    */
    'models' => [
        'namespace' => 'Bu\\DAL\\Models',
        'fillable_protection' => env('DAL_FILLABLE_PROTECTION', true),
        'mass_assignment_protection' => env('DAL_MASS_ASSIGNMENT_PROTECTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for the DAL package.
    |
    */
    'logging' => [
        'enabled' => env('DAL_LOGGING_ENABLED', true),
        'level' => env('DAL_LOG_LEVEL', 'info'),
        'channel' => env('DAL_LOG_CHANNEL', 'daily'),
    ],
];
