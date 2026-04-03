<?php

namespace Oluokunkabiru\AuditTrail;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Oluokunkabiru\AuditTrail\Data\AuditEntry;
use Oluokunkabiru\AuditTrail\Drivers\Contracts\AuditDriver;
use Oluokunkabiru\AuditTrail\Engine\ContextResolver;
use Oluokunkabiru\AuditTrail\Engine\DiffEngine;
use Oluokunkabiru\AuditTrail\Events\AuditLogged;
use Oluokunkabiru\AuditTrail\Jobs\WriteAuditLog;

class AuditManager
{
    protected bool $suppressed = false;

    public function __construct(
        protected DiffEngine      $diffEngine,
        protected ContextResolver $contextResolver,
        protected AuditDriver     $driver,
    ) {}

    /**
     * Record an audit event for the given model.
     */
    public function record(Model $model, string $event): void
    {
        if ($this->suppressed) {
            return;
        }

        $diff = $this->diffEngine->compute($model, $event);

        // Skip if nothing actually changed (e.g. touching updated_at only)
        if ($event === 'updated' && empty($diff['before']) && empty($diff['after'])) {
            return;
        }

        $context = $this->contextResolver->resolve();

        $entry = AuditEntry::fromModel($model, $event, $diff['before'], $diff['after'], $context);

        $this->write($entry);
    }

    /**
     * Write the entry — queued or synchronous depending on config.
     */
    protected function write(AuditEntry $entry): void
    {
        if (config('audit.queue.enabled', false)) {
            $job = (new WriteAuditLog($entry))
                ->onConnection(config('audit.queue.connection'))
                ->onQueue(config('audit.queue.queue_name', 'audits'));

            dispatch($job);
        } else {
            $this->driver->log($entry);
        }

        event(new AuditLogged($entry));
    }

    /**
     * Suppress all audit logging within the given callback.
     *
     * Usage:
     *   Auditor::suppress(function () {
     *       User::factory()->count(1000)->create();
     *   });
     */
    public function suppress(Closure $callback): mixed
    {
        $this->suppressed = true;

        try {
            return $callback();
        } finally {
            $this->suppressed = false;
        }
    }

    public function isSuppressed(): bool
    {
        return $this->suppressed;
    }

    public function driver(): AuditDriver
    {
        return $this->driver;
    }

    /**
     * Manually log a custom audit event (not tied to a model event).
     */
    public function log(Model $model, string $customEvent, array $before = [], array $after = []): void
    {
        $context = $this->contextResolver->resolve();
        $entry   = AuditEntry::fromModel($model, $customEvent, $before, $after, $context);
        $this->write($entry);
    }
}
