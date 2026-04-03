<?php

namespace Oluokunkabiru\AuditTrail;

use Illuminate\Support\ServiceProvider;
use Oluokunkabiru\AuditTrail\AuditManager;
use Oluokunkabiru\AuditTrail\Drivers\Contracts\AuditDriver;
use Oluokunkabiru\AuditTrail\Drivers\DatabaseDriver;
use Oluokunkabiru\AuditTrail\Engine\ContextResolver;
use Oluokunkabiru\AuditTrail\Engine\DiffEngine;

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

        // Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'audit-trail');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/audit-trail'),
        ], 'audit-trail-views');

        // Livewire Component
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('audit-trail-dashboard', \Oluokunkabiru\AuditTrail\Http\Livewire\Dashboard::class);
        }

        // Routes
        if (config('audit.dashboard_enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }
}
