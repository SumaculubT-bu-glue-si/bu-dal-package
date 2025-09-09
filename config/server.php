<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configure authentication settings for the server package.
    |
    */
    'auth' => [
        'defaults' => [
            'guard' => env('SERVER_AUTH_GUARD', 'api'),
            'passwords' => 'users',
        ],
        'guards' => [
            'api' => [
                'driver' => 'sanctum',
                'provider' => 'users',
            ],
        ],
        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                'model' => \Bu\Server\Models\User::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GraphQL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure GraphQL settings including schema, middleware, and caching.
    |
    */
    'graphql' => [
        'prefix' => 'graphql',
        'middleware' => ['auth:sanctum'],
        'schema' => [
            'register' => base_path('graphql/schema.graphql'),
        ],
        'cache' => [
            'enable' => env('GRAPHQL_CACHE_ENABLE', true),
            'ttl' => env('GRAPHQL_CACHE_TTL', 3600), // 1 hour
        ],
        'pagination' => [
            'default_count' => env('GRAPHQL_DEFAULT_COUNT', 10),
            'max_count' => env('GRAPHQL_MAX_COUNT', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Configure email notification settings and templates.
    |
    */
    'mail' => [
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Example'),
        ],
        'notifications' => [
            'audit_reminder' => [
                'enabled' => env('AUDIT_REMINDER_EMAILS_ENABLED', true),
                'template' => 'server::emails.audit-reminder',
                'frequency' => env('AUDIT_REMINDER_FREQUENCY', 'daily'),
            ],
            'corrective_action' => [
                'enabled' => env('CORRECTIVE_ACTION_EMAILS_ENABLED', true),
                'template' => 'server::emails.corrective-action',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit System Configuration
    |--------------------------------------------------------------------------
    |
    | Configure audit logging, retention, and notification settings.
    |
    */
    'audit' => [
        'enabled' => env('AUDIT_SYSTEM_ENABLED', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        'notifications' => [
            'reminders' => [
                'enabled' => true,
                'frequency' => env('AUDIT_REMINDER_FREQUENCY', 'daily'),
                'time' => env('AUDIT_REMINDER_TIME', '09:00'),
            ],
        ],
        'log_user_actions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database connection and table prefix settings.
    |
    */
    'database' => [
        'connection' => env('SERVER_DB_CONNECTION', null),
        'prefix' => env('SERVER_DB_PREFIX', ''),
        'migrations' => [
            'table' => 'server_migrations',
            'path' => database_path('migrations'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure file storage settings for attachments and uploads.
    |
    */
    'storage' => [
        'disk' => env('SERVER_STORAGE_DISK', 'local'),
        'path' => env('SERVER_STORAGE_PATH', 'server'),
        'allowed_types' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'
        ],
        'max_size' => env('SERVER_MAX_UPLOAD_SIZE', 10240), // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching settings for improved performance.
    |
    */
    'cache' => [
        'enabled' => env('SERVER_CACHE_ENABLED', true),
        'prefix' => 'server_',
        'ttl' => env('SERVER_CACHE_TTL', 3600), // 1 hour
        'store' => env('SERVER_CACHE_STORE', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CORS for client application access.
    |
    */
    'cors' => [
        'allowed_origins' => explode(',', env('SERVER_CORS_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | REST API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure REST API settings including routes and middleware.
    |
    */
    'api' => [
        'prefix' => env('SERVER_API_PREFIX', 'api'),
        'middleware' => ['api', 'auth:sanctum'],
        'throttle' => [
            'enabled' => true,
            'limit' => env('SERVER_API_RATE_LIMIT', 60),
            'per_minutes' => 1
        ],
        'version' => '1.0',
    ],
];
