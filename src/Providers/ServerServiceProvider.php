<?php

namespace Bu\Server\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Bu\Server\Services\AuditNotificationService;
use Bu\Server\Services\CorrectiveActionNotificationService;
use Bu\Server\Services\AuditAccessService;
use Bu\Server\Providers\MailServiceProvider;

class ServerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/server.php',
            'server'
        );

        // Register console commands
        // $this->app->register(ConsoleServiceProvider::class);

        // Register services
        $this->registerServices();

        // Register mail service provider
        $this->app->register(MailServiceProvider::class);

        // Register notification services
        $this->app->singleton(AuditNotificationService::class);
        $this->app->singleton(CorrectiveActionNotificationService::class);
        $this->app->singleton(AuditAccessService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/server.php' => config_path('server.php'),
            __DIR__ . '/../../config/cors.php' => config_path('cors.php'),
            __DIR__ . '/../../config/app.php' => config_path('app.php'),
            __DIR__ . '/../../config/lighthouse.php' => config_path('lighthouse.php'),
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
            __DIR__ . '/../../graphql' => base_path('graphql'),
            __DIR__ . '/../../resources/views' => resource_path('views/server'),
            __DIR__ . '/RouteServiceProvider.php' => app_path('Providers/RouteServiceProvider.php'),
        ], 'server-all');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/server.php' => config_path('server.php'),
        ], 'server-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'server-migrations');

        // Publish GraphQL schema
        $this->publishes([
            __DIR__ . '/../../graphql' => base_path('graphql'),
        ], 'server-graphql');

        // Publish email views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/server'),
        ], 'server-views');

        // Register email views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'server');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
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
}
