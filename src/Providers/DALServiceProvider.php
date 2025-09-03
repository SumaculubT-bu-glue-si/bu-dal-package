<?php

namespace Bu\DAL\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Bu\DAL\Database\DatabaseManager;
use Bu\DAL\Database\Repositories\AssetRepository;
use Bu\DAL\Database\Repositories\EmployeeRepository;
use Bu\DAL\Database\Repositories\LocationRepository;
use Bu\DAL\Database\Repositories\ProjectRepository;
use Bu\DAL\Database\Repositories\UserRepository;
use Bu\DAL\Database\Repositories\AuditPlanRepository;
use Bu\DAL\Database\Repositories\AuditAssetRepository;
use Bu\DAL\Database\Repositories\AuditAssignmentRepository;
use Bu\DAL\Database\Repositories\CorrectiveActionRepository;
use Bu\DAL\Database\Repositories\CorrectiveActionAssignmentRepository;
use Bu\DAL\Services\AuditNotificationService;
use Bu\DAL\Models\Asset;
use Bu\DAL\Models\Employee;
use Bu\DAL\Models\Location;
use Bu\DAL\Models\Project;
use Bu\DAL\Models\User;
use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Models\AuditAssignment;
use Bu\DAL\Models\CorrectiveAction;
use Bu\DAL\Models\CorrectiveActionAssignment;

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
        }

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

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
