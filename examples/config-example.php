<?php

/**
 * Configuration example for Laravel GraphQL DAL Package
 * 
 * This file shows how to configure the package in your Laravel application
 */

return [
    // GraphQL DAL Configuration
    'graphql-dal' => [
        'schema' => [
            'register' => true, // Whether to register the package's GraphQL schema
            'path' => base_path('vendor/yourcompany/laravel-graphql-dal/graphql/schema.graphql'),
            'namespaces' => [
                'models' => ['YourCompany\\GraphQLDAL\\Models'],
                'queries' => 'YourCompany\\GraphQLDAL\\GraphQL\\Queries',
                'mutations' => 'YourCompany\\GraphQLDAL\\GraphQL\\Mutations',
                'types' => 'YourCompany\\GraphQLDAL\\GraphQL\\Types',
                'inputs' => 'YourCompany\\GraphQLDAL\\GraphQL\\Inputs',
            ],
        ],
        'database' => [
            'default_connection' => env('GRAPHQL_DAL_DB_CONNECTION', null), // Use host app's default if null
        ],
    ],

    // Database DAL Configuration
    'database-dal' => [
        'connections' => [
            // Define any specific database connections for the DAL here
            // For example, if the DAL needs to connect to a different database
            // than the main application for certain operations.
            'dal_mysql' => [
                'driver' => 'mysql',
                'host' => env('DAL_DB_HOST', '127.0.0.1'),
                'port' => env('DAL_DB_PORT', '3306'),
                'database' => env('DAL_DB_DATABASE', 'forge'),
                'username' => env('DAL_DB_USERNAME', 'forge'),
                'password' => env('DAL_DB_PASSWORD', ''),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ],
        ],
        'default' => env('GRAPHQL_DAL_DB_CONNECTION', null), // If null, it will use the host app's default connection
    ],

    // Example .env configuration
    'env_example' => [
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_DATABASE' => 'your_database_name',
        'DB_USERNAME' => 'your_username',
        'DB_PASSWORD' => 'your_password',

        // Optional: DAL-specific database connection
        'GRAPHQL_DAL_DB_CONNECTION' => 'mysql', // or 'dal_mysql' if using separate connection

        // Optional: DAL-specific database settings
        'DAL_DB_HOST' => '127.0.0.1',
        'DAL_DB_PORT' => '3306',
        'DAL_DB_DATABASE' => 'your_dal_database_name',
        'DAL_DB_USERNAME' => 'your_dal_username',
        'DAL_DB_PASSWORD' => 'your_dal_password',
    ],

    // Example service provider registration in config/app.php
    'service_provider_example' => [
        'providers' => [
            // ... other providers
            YourCompany\GraphQLDAL\Providers\GraphQLDALServiceProvider::class,
        ],
    ],

    // Example GraphQL configuration in config/graphql.php
    'graphql_config_example' => [
        'schema' => [
            'register' => [
                'default' => \YourCompany\GraphQLDAL\GraphQL\Schema\GraphQLDALSchema::class,
            ],
        ],
        'namespaces' => [
            'models' => ['YourCompany\\GraphQLDAL\\Models'],
            'queries' => 'YourCompany\\GraphQLDAL\\GraphQL\\Queries',
            'mutations' => 'YourCompany\\GraphQLDAL\\GraphQL\\Mutations',
            'types' => 'YourCompany\\GraphQLDAL\\GraphQL\\Types',
            'inputs' => 'YourCompany\\GraphQLDAL\\GraphQL\\Inputs',
        ],
    ],
];
