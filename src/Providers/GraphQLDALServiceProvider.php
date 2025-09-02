<?php

namespace YourCompany\GraphQLDAL\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use YourCompany\GraphQLDAL\Database\DatabaseManager;
use YourCompany\GraphQLDAL\Database\TransactionManager;
use YourCompany\GraphQLDAL\Database\Repositories\AssetRepository;
use YourCompany\GraphQLDAL\Database\Repositories\EmployeeRepository;
use YourCompany\GraphQLDAL\Database\Repositories\LocationRepository;
use YourCompany\GraphQLDAL\Database\Repositories\ProjectRepository;
use YourCompany\GraphQLDAL\Database\Repositories\UserRepository;
use YourCompany\GraphQLDAL\Database\Repositories\AuditPlanRepository;
use YourCompany\GraphQLDAL\Database\Repositories\AuditAssetRepository;
use YourCompany\GraphQLDAL\Database\Repositories\AuditAssignmentRepository;
use YourCompany\GraphQLDAL\Database\Repositories\CorrectiveActionRepository;
use YourCompany\GraphQLDAL\Database\Repositories\CorrectiveActionAssignmentRepository;

class GraphQLDALServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/graphql-dal.php', 'graphql-dal');
        $this->mergeConfigFrom(__DIR__ . '/../../config/database-dal.php', 'database-dal');

        // Register database services
        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager($app['db']);
        });

        $this->app->singleton(TransactionManager::class, function ($app) {
            return new TransactionManager($app[DatabaseManager::class]);
        });

        // Register repositories
        $this->app->bind(AssetRepository::class, function ($app) {
            return new AssetRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(EmployeeRepository::class, function ($app) {
            return new EmployeeRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(LocationRepository::class, function ($app) {
            return new LocationRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(ProjectRepository::class, function ($app) {
            return new ProjectRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(UserRepository::class, function ($app) {
            return new UserRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(AuditPlanRepository::class, function ($app) {
            return new AuditPlanRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(AuditAssetRepository::class, function ($app) {
            return new AuditAssetRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(AuditAssignmentRepository::class, function ($app) {
            return new AuditAssignmentRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(CorrectiveActionRepository::class, function ($app) {
            return new CorrectiveActionRepository($app[DatabaseManager::class]);
        });

        $this->app->bind(CorrectiveActionAssignmentRepository::class, function ($app) {
            return new CorrectiveActionAssignmentRepository($app[DatabaseManager::class]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration files
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/graphql-dal.php' => config_path('graphql-dal.php'),
                __DIR__ . '/../../config/database-dal.php' => config_path('database-dal.php'),
            ], 'graphql-dal-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'graphql-dal-migrations');

            // Publish seeders
            $this->publishes([
                __DIR__ . '/../../database/seeders' => database_path('seeders'),
            ], 'graphql-dal-seeders');

            // Publish GraphQL schema
            $this->publishes([
                __DIR__ . '/../../graphql/schema.graphql' => base_path('graphql/schema.graphql'),
            ], 'graphql-dal-schema');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
