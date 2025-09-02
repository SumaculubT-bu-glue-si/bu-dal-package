<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database DAL Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the database-specific configuration for the DAL package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for database connections used by the DAL.
    |
    */
    'connections' => [
        'primary' => [
            'driver' => env('DB_CONNECTION', 'mysql'),
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

        'secondary' => [
            'driver' => env('DB_SECONDARY_CONNECTION', 'mysql'),
            'host' => env('DB_SECONDARY_HOST', '127.0.0.1'),
            'port' => env('DB_SECONDARY_PORT', '3306'),
            'database' => env('DB_SECONDARY_DATABASE', 'laravel'),
            'username' => env('DB_SECONDARY_USERNAME', 'root'),
            'password' => env('DB_SECONDARY_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failover Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic failover between database connections.
    |
    */
    'failover' => [
        'enabled' => env('DB_DAL_FAILOVER_ENABLED', false),
        'connections' => ['primary', 'secondary'],
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Pool Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for database connection pooling.
    |
    */
    'pool' => [
        'enabled' => env('DB_DAL_POOL_ENABLED', true),
        'min_connections' => env('DB_DAL_MIN_CONNECTIONS', 1),
        'max_connections' => env('DB_DAL_MAX_CONNECTIONS', 10),
        'idle_timeout' => env('DB_DAL_IDLE_TIMEOUT', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for query execution and optimization.
    |
    */
    'queries' => [
        'timeout' => env('DB_DAL_QUERY_TIMEOUT', 30), // seconds
        'max_execution_time' => env('DB_DAL_MAX_EXECUTION_TIME', 60), // seconds
        'enable_query_logging' => env('DB_DAL_QUERY_LOGGING', false),
        'slow_query_threshold' => env('DB_DAL_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for database migrations.
    |
    */
    'migrations' => [
        'path' => database_path('migrations'),
        'table' => 'migrations',
        'batch_size' => 1000,
    ],
];
