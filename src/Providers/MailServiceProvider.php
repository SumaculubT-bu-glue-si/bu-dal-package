<?php

namespace Bu\Server\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default mail configuration if not set
        if (!Config::has('mail.from.address')) {
            Config::set('mail.from.address', 'noreply@example.com');
        }

        if (!Config::has('mail.from.name')) {
            Config::set('mail.from.name', config('app.name', 'Laravel'));
        }

        // Load email views from package
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'bu-server');
    }
}
