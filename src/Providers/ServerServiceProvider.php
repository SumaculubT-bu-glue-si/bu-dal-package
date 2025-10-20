<?php

namespace Bu\Server\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Bu\Server\Database\DatabaseManager;
use Bu\Server\Database\Repositories\AssetRepository;
use Bu\Server\Database\Repositories\EmployeeRepository;
use Bu\Server\Database\Repositories\LocationRepository;
use Bu\Server\Database\Repositories\ProjectRepository;
use Bu\Server\Database\Repositories\UserRepository;
use Bu\Server\Database\Repositories\AuditPlanRepository;
use Bu\Server\Database\Repositories\AuditAssetRepository;
use Bu\Server\Database\Repositories\AuditAssignmentRepository;
use Bu\Server\Database\Repositories\CorrectiveActionRepository;
use Bu\Server\Database\Repositories\CorrectiveActionAssignmentRepository;
use Bu\Server\Services\AuditNotificationService;
use Bu\Server\Services\CorrectiveActionNotificationService;
use Bu\Server\Services\AuditAccessService;
use Bu\Server\Providers\MailServiceProvider;
use Bu\Server\Models\Asset;
use Bu\Server\Models\Employee;
use Bu\Server\Models\Location;
use Bu\Server\Models\Project;
use Bu\Server\Models\User;
use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\AuditAsset;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\CorrectiveAction;
use Bu\Server\Models\CorrectiveActionAssignment;

class ServerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register package configurations
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dal.php',
            'dal'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/server.php',
            'server'
        );

        // Register the database manager
        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager();
        });

        // Register repositories
        $this->app->singleton(AssetRepository::class, function ($app) {
            return new AssetRepository(new Asset());
        });

        $this->app->singleton(EmployeeRepository::class, function ($app) {
            return new EmployeeRepository(new Employee());
        });

        $this->app->singleton(LocationRepository::class, function ($app) {
            return new LocationRepository(new Location());
        });

        $this->app->singleton(ProjectRepository::class, function ($app) {
            return new ProjectRepository(new Project());
        });

        $this->app->singleton(UserRepository::class, function ($app) {
            return new UserRepository(new User());
        });

        $this->app->singleton(AuditPlanRepository::class, function ($app) {
            return new AuditPlanRepository(new AuditPlan());
        });

        $this->app->singleton(AuditAssetRepository::class, function ($app) {
            return new AuditAssetRepository(new AuditAsset());
        });

        $this->app->singleton(AuditAssignmentRepository::class, function ($app) {
            return new AuditAssignmentRepository(new AuditAssignment());
        });

        $this->app->singleton(CorrectiveActionRepository::class, function ($app) {
            return new CorrectiveActionRepository(new CorrectiveAction());
        });

        $this->app->singleton(CorrectiveActionAssignmentRepository::class, function ($app) {
            return new CorrectiveActionAssignmentRepository(new CorrectiveActionAssignment());
        });

        // Register services
        $this->app->singleton(AuditNotificationService::class, function ($app) {
            return new AuditNotificationService(
                $app->make(LocationRepository::class),
                $app->make(EmployeeRepository::class),
                $app->make(DatabaseManager::class)
            );
        });

        $this->app->singleton(CorrectiveActionNotificationService::class, function ($app) {
            return new CorrectiveActionNotificationService();
        });

        $this->app->singleton(AuditAccessService::class);

        // Register mail service provider
        $this->app->register(MailServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load package API routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        // Register email views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'server');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Bu\Server\Console\Commands\SendAuditReminders::class,
                \Bu\Server\Console\Commands\SendCorrectiveActionReminders::class,
                \Bu\Server\Console\Commands\TestAuditPlanAccess::class,
                \Bu\Server\Console\Commands\TestAuditSystem::class,
            ]);
        }

        // Register GraphQL schema if enabled
        if (Config::get('dal.graphql.enabled', true)) {
            $this->registerGraphQLSchema();
        }

        // Publish all resources
        if ($this->app->runningInConsole()) {
            // Publish all resources at once
            $this->publishes([
                __DIR__ . '/../../config/dal.php' => config_path('dal.php'),
                __DIR__ . '/../../config/server.php' => config_path('server.php'),
                __DIR__ . '/../../config/cors.php' => config_path('cors.php'),
                __DIR__ . '/../../config/lighthouse.php' => config_path('lighthouse.php'),
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
                __DIR__ . '/../../graphql/schema.graphql' => base_path('graphql/schema.graphql'),
                __DIR__ . '/../../resources/views/emails' => resource_path('views/emails'),
                __DIR__ . '/../../resources/views/graphql-playground.blade.php' => resource_path('views/graphql-playground.blade.php'),
                __DIR__ . '/../../resources/Http/Controllers' => app_path('Http/Controllers'),
            ], 'server-all');

            // Individual publish groups
            $this->publishes([
                __DIR__ . '/../../config/dal.php' => config_path('dal.php'),
            ], 'dal-config');

            $this->publishes([
                __DIR__ . '/../../config/server.php' => config_path('server.php'),
            ], 'server-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'server-migrations');

            $this->publishes([
                __DIR__ . '/../../graphql/schema.graphql' => base_path('graphql/schema.graphql'),
            ], 'server-graphql');

            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/server'),
            ], 'server-views');
        }
    }

    /**
     * Register package services.
     */
    protected function registerServices(): void
    {
        // Register notification services
        $this->app->singleton(AuditNotificationService::class);
        $this->app->singleton(CorrectiveActionNotificationService::class);
    }

    /**
     * Register GraphQL schema and resolvers.
     */
    protected function registerGraphQLSchema(): void
    {
        // This would typically register the GraphQL schema with Lighthouse
        // For now, we'll just ensure the schema file exists
        $schemaPath = Config::get('dal.graphql.schema_path', 'graphql/schema.graphql');

        if (file_exists(base_path($schemaPath))) {
            Config::set('lighthouse.schema_path', base_path($schemaPath));
        }
    }
}
