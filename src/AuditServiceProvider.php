<?php

namespace YourVendor\AuditTrail;

use Illuminate\Support\ServiceProvider;
use YourVendor\AuditTrail\AuditManager;
use YourVendor\AuditTrail\Drivers\Contracts\AuditDriver;
use YourVendor\AuditTrail\Drivers\DatabaseDriver;
use YourVendor\AuditTrail\Engine\ContextResolver;
use YourVendor\AuditTrail\Engine\DiffEngine;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/audit.php', 'audit');

        // Bind the active driver
        $this->app->singleton(AuditDriver::class, function ($app) {
            $driver = config('audit.driver', 'database');

            return match ($driver) {
                'database' => new DatabaseDriver(),
                default    => $app->make($driver),
            };
        });

        // Bind engine components
        $this->app->singleton(DiffEngine::class, function () {
            return new DiffEngine(
                globalExclude: config('audit.global_exclude', []),
            );
        });

        $this->app->singleton(ContextResolver::class, fn() => new ContextResolver());

        // Bind the manager
        $this->app->singleton(AuditManager::class, function ($app) {
            return new AuditManager(
                diffEngine:      $app->make(DiffEngine::class),
                contextResolver: $app->make(ContextResolver::class),
                driver:          $app->make(AuditDriver::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/audit.php' => config_path('audit.php'),
            ], 'audit-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'audit-migrations');

            // Register Artisan commands
            $this->commands([
                Commands\PruneAuditLogsCommand::class,
            ]);
        }

        // Always load migrations so they can be run without publishing
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
