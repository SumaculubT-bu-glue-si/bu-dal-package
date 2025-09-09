<?php

namespace Bu\Server\Providers;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Bu\Server\Console\Commands\SendAuditReminders::class,
                \Bu\Server\Console\Commands\SendCorrectiveActionReminders::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        //
    }
}
