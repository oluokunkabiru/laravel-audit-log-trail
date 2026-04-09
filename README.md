# laravel-audit-trail

A fluent, diff-aware audit trail for Eloquent models. Logs exactly what changed, who changed it, and when — with multi-tenant support, a swappable driver system, and a clean suppression API.

## Installation

```bash
composer require oluokunkabiru/laravel-audit-log-trail
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=audit-migrations
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=audit-config
```

## Quick start

Add the `HasAuditTrail` trait to any Eloquent model:

```php
use Oluokunkabiru\AuditTrail\Traits\HasAuditTrail;

class User extends Model
{
    use HasAuditTrail;
}
```

That's it. Every `created`, `updated`, `deleted`, and `restored` event is now logged automatically.

## Control Dashboard

The package comes with a beautiful, Livewire-powered control dashboard out of the box! 
Access the dashboard at `/audit-trail` in your browser to manage your audit logs.

Features available from the Dashboard include:
- **Discover Models**: Automatically detect and list all Eloquent models in your project.
- **Enable/Disable Tracking**: Instantly inject or remove the `HasAuditTrail` trait from your PHP models with a single click.
- **Global Settings**: Configure the audit storage driver, background queues, and pruning policies directly from the UI natively connecting back to your `.env` file.

*Requires Livewire 3.*

## Controlling which fields are logged

```php
class User extends Model
{
    use HasAuditTrail;

    // Only log these fields
    protected array $auditInclude = ['name', 'email', 'role'];

    // Or: exclude specific fields (stacks with global_exclude in config)
    protected array $auditExclude = ['api_token', 'two_factor_secret'];
}
```

## Querying audit logs

```php
use Oluokunkabiru\AuditTrail\Models\AuditLog;

// All changes to a specific model instance
AuditLog::forModel($user)->latest()->get();

// Filter by event type
AuditLog::forModel($user)->event('updated')->get();

// Who changed the email field?
AuditLog::forModel($user)->forField('email')->get();

// All deletes in the last 7 days
AuditLog::event('deleted')->since(now()->subDays(7))->get();

// By actor
AuditLog::byActor($admin)->today()->get();
```

Via the model relationship:

```php
$user->auditLogs()->latest()->limit(10)->get();
$user->latestAudit;
```

## Suppression

```php
use Oluokunkabiru\AuditTrail\Facades\Auditor;

// Suppress all logging inside the callback
Auditor::suppress(function () {
    User::factory()->count(1000)->create();
});

// Suppress on a specific model instance
$user->withoutAudit(fn() => $user->update(['name' => 'Silent Bob']));
```

## Custom events

```php
Auditor::log($user, 'password_reset', before: ['method' => 'email']);
Auditor::log($order, 'status_changed', before: ['status' => 'pending'], after: ['status' => 'paid']);
```

## Multi-tenancy

In `config/audit.php`:

```php
'tenant_resolver' => fn() => auth()->user()?->organization_id,
```

Query scoped to a tenant:

```php
AuditLog::forTenant($tenantId)->latest()->get();
```

## Async writes (recommended for production)

```env
AUDIT_QUEUE_ENABLED=true
AUDIT_QUEUE_CONNECTION=redis
AUDIT_QUEUE_NAME=audits
```

## Pruning old logs

```bash
# Delete logs older than 365 days (or your configured retention)
php artisan audit:prune

# Override days via option
php artisan audit:prune --days=90
```

Schedule it in `routes/console.php`:

```php
Schedule::command('audit:prune')->daily();
```

## Configuration reference

```php
// config/audit.php
return [
    'driver'          => 'database',
    'table'           => 'audit_logs',
    'queue'           => ['enabled' => false, 'connection' => null, 'queue_name' => 'audits'],
    'tenant_resolver' => null,
    'actor_resolver'  => null,
    'prune'           => ['keep_days' => 365],
    'global_exclude'  => ['password', 'remember_token'],
    'metadata'        => ['capture_url' => true, 'capture_ip' => true, 'capture_user_agent' => false],
];
```

## Custom drivers

Implement the `AuditDriver` contract and bind it in a service provider:

```php
use Oluokunkabiru\AuditTrail\Drivers\Contracts\AuditDriver;

class ElasticsearchAuditDriver implements AuditDriver
{
    public function log(AuditEntry $entry): void { /* ... */ }
    public function prune(int $keepDays): int { /* ... */ }
}

// In AppServiceProvider::register():
$this->app->bind(AuditDriver::class, ElasticsearchAuditDriver::class);
```

## Listening to audit events

```php
use Oluokunkabiru\AuditTrail\Events\AuditLogged;

Event::listen(AuditLogged::class, function (AuditLogged $event) {
    // $event->entry is an AuditEntry value object
    logger()->info('Audit recorded', $event->entry->toArray());
});
```

## Running tests

```bash
composer test
```

## License

MIT
