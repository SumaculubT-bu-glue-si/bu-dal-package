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

class DALServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dal.php',
            'dal'
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load package API routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        // Publish configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/dal.php' => config_path('dal.php'),
            ], 'dal-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'dal-migrations');

            // Publish GraphQL schema
            $this->publishes([
                __DIR__ . '/../../graphql/schema.graphql' => base_path('graphql/schema.graphql'),
            ], 'dal-graphql');

            // Publish email templates
            $this->publishes([
                __DIR__ . '/../../resources/views/emails' => resource_path('views/emails'),
            ], 'dal-email-templates');

            // Publish additional views
            $this->publishes([
                __DIR__ . '/../../resources/views/graphql-playground.blade.php' => resource_path('views/graphql-playground.blade.php'),
            ], 'dal-views');

            // Publish HTTP controllers
            $this->publishes([
                __DIR__ . '/../../resources/Http/Controllers' => app_path('Http/Controllers'),
            ], 'dal-controllers');

            // Publish all resources at once
            $this->publishes([
                __DIR__ . '/../../config/dal.php' => config_path('dal.php'),
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
                __DIR__ . '/../../graphql/schema.graphql' => base_path('graphql/schema.graphql'),
                __DIR__ . '/../../resources/views/emails' => resource_path('views/emails'),
                __DIR__ . '/../../resources/views/graphql-playground.blade.php' => resource_path('views/graphql-playground.blade.php'),
                __DIR__ . '/../../resources/Http/Controllers' => app_path('Http/Controllers'),
            ], 'dal-all');
        }

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Bu\Server\Console\Commands\InstallDALPackage::class,
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
