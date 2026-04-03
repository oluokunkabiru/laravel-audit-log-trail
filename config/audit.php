<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Audit Driver
    |--------------------------------------------------------------------------
    | Supported: "database"
    | You can add custom drivers by binding them in a service provider.
    */
    'driver' => env('AUDIT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Audit Table Name
    |--------------------------------------------------------------------------
    */
    'table' => env('AUDIT_TABLE', 'audit_logs'),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    | When enabled, audit writes are dispatched as queued jobs so they don't
    | add latency to the request lifecycle.
    */
    'queue' => [
        'enabled'    => env('AUDIT_QUEUE_ENABLED', false),
        'connection' => env('AUDIT_QUEUE_CONNECTION', null), // null = default connection
        'queue_name' => env('AUDIT_QUEUE_NAME', 'audits'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolver
    |--------------------------------------------------------------------------
    | A callable that returns the current tenant identifier. Return null if
    | your app is single-tenant. Works with any tenancy package.
    |
    | Example:
    |   'tenant_resolver' => fn() => auth()->user()?->organization_id,
    */
    'tenant_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Actor Resolver
    |--------------------------------------------------------------------------
    | By default the package uses auth()->user(). Override this closure to
    | resolve the actor from a different source (e.g. API token, CLI command).
    */
    'actor_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    | Logs older than `keep_days` will be deleted when you run the
    | audit:prune Artisan command (or schedule it).
    */
    'prune' => [
        'keep_days' => env('AUDIT_KEEP_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Exclude
    |--------------------------------------------------------------------------
    | Attribute names listed here are excluded from ALL models' diffs, in
    | addition to each model's own $auditExclude array.
    */
    'global_exclude' => [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    | Extra metadata captured with every audit entry.
    */
    'metadata' => [
        'capture_url'        => true,
        'capture_user_agent' => false, // enable if needed (privacy implications)
        'capture_ip'         => true,
    ],

];
