<?php

namespace YourVendor\AuditTrail\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string      $id            ULID
 * @property string      $event
 * @property string      $auditable_type
 * @property string      $auditable_id
 * @property string|null $actor_type
 * @property string|null $actor_id
 * @property string|null $actor_ip
 * @property string|null $tenant_id
 * @property array|null  $before
 * @property array|null  $after
 * @property string|null $url
 * @property array|null  $metadata
 * @property \Carbon\Carbon $created_at
 */
class AuditLog extends Model
{
    public $timestamps     = false;
    public $incrementing   = false;
    protected $keyType     = 'string';

    protected $guarded = [];

    protected $casts = [
        'before'     => 'array',
        'after'      => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('audit.table', 'audit_logs');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function auditable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    /** Filter by a specific model instance */
    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query
            ->where('auditable_type', get_class($model))
            ->where('auditable_id', (string) $model->getKey());
    }

    /** Filter by model class (all records of that type) */
    public function scopeForModelType(Builder $query, string $modelClass): Builder
    {
        return $query->where('auditable_type', $modelClass);
    }

    /** Filter by actor model instance */
    public function scopeByActor(Builder $query, Model $actor): Builder
    {
        return $query
            ->where('actor_type', get_class($actor))
            ->where('actor_id', (string) $actor->getKey());
    }

    /** Filter by event type */
    public function scopeEvent(Builder $query, string ...$events): Builder
    {
        return count($events) === 1
            ? $query->where('event', $events[0])
            : $query->whereIn('event', $events);
    }

    /** Filter logs where a specific field was changed */
    public function scopeForField(Builder $query, string $field): Builder
    {
        return $query->where(function (Builder $q) use ($field) {
            $q->whereJsonContainsKey("before->{$field}")
              ->orWhereJsonContainsKey("after->{$field}");
        });
    }

    /** Filter by tenant */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /** Logs from the last N days */
    public function scopeSince(Builder $query, \DateTimeInterface|string $date): Builder
    {
        return $query->where('created_at', '>=', $date);
    }

    /** Only today's logs */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Returns a flat list of field names that changed in this entry */
    public function changedFields(): array
    {
        return array_unique(array_merge(
            array_keys($this->before ?? []),
            array_keys($this->after ?? []),
        ));
    }

    /** Returns the old value of a specific field */
    public function oldValue(string $field): mixed
    {
        return ($this->before ?? [])[$field] ?? null;
    }

    /** Returns the new value of a specific field */
    public function newValue(string $field): mixed
    {
        return ($this->after ?? [])[$field] ?? null;
    }
}
