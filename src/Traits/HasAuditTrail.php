<?php

namespace YourVendor\AuditTrail\Traits;

use Closure;
use YourVendor\AuditTrail\Facades\Auditor;

/**
 * Mix this trait into any Eloquent model to enable audit logging.
 *
 * Usage:
 *   class User extends Model
 *   {
 *       use HasAuditTrail;
 *
 *       // Optional: only log these fields
 *       protected array $auditInclude = ['name', 'email', 'role'];
 *
 *       // Optional: never log these fields (stacked with global_exclude in config)
 *       protected array $auditExclude = ['api_token'];
 *   }
 */
trait HasAuditTrail
{
    /**
     * Fields to include in diffs. If non-empty, only these fields are logged.
     * If empty, all fields are logged (minus $auditExclude and global_exclude).
     */
    // protected array $auditInclude = [];

    /**
     * Fields to always exclude from diffs on this model.
     */
    // protected array $auditExclude = [];

    /**
     * Whether auditing is currently suppressed for this model instance.
     */
    protected bool $auditingSuppressed = false;

    public static function bootHasAuditTrail(): void
    {
        static::created(fn($model) => $model->recordAudit('created'));
        static::updated(fn($model) => $model->recordAudit('updated'));
        static::deleted(fn($model) => $model->recordAudit('deleted'));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn($model) => $model->recordAudit('restored'));
        }
    }

    /**
     * Dispatch an audit for the given event, unless suppressed.
     */
    public function recordAudit(string $event): void
    {
        if ($this->auditingSuppressed || Auditor::isSuppressed()) {
            return;
        }

        Auditor::record($this, $event);
    }

    /**
     * Execute a callback without logging any audit events on this model instance.
     */
    public function withoutAudit(Closure $callback): mixed
    {
        $this->auditingSuppressed = true;

        try {
            return $callback();
        } finally {
            $this->auditingSuppressed = false;
        }
    }

    /**
     * Retrieve all audit logs for this model instance.
     */
    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(
            config('audit.model', \YourVendor\AuditTrail\Models\AuditLog::class),
            'auditable',
        );
    }

    /**
     * Retrieve the latest audit log entry for this model instance.
     */
    public function latestAudit(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(
            config('audit.model', \YourVendor\AuditTrail\Models\AuditLog::class),
            'auditable',
        )->latestOfMany();
    }
}
