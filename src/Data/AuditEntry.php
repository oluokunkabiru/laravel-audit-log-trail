<?php

namespace YourVendor\AuditTrail\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable value object representing a single audit log entry.
 * Built by AuditEntryBuilder, consumed by AuditDriver implementations.
 */
final class AuditEntry
{
    public function __construct(
        public readonly string  $event,
        public readonly string  $auditableType,
        public readonly string  $auditableId,
        public readonly ?string $actorType,
        public readonly ?string $actorId,
        public readonly ?string $actorIp,
        public readonly ?string $tenantId,
        public readonly array   $before,
        public readonly array   $after,
        public readonly ?string $url,
        public readonly array   $metadata,
        public readonly \DateTimeInterface $createdAt,
    ) {}

    public static function fromModel(
        Model  $model,
        string $event,
        array  $before,
        array  $after,
        array  $context = [],
    ): self {
        return new self(
            event:         $event,
            auditableType: get_class($model),
            auditableId:   (string) $model->getKey(),
            actorType:     $context['actor_type'] ?? null,
            actorId:       $context['actor_id'] ?? null,
            actorIp:       $context['actor_ip'] ?? null,
            tenantId:      $context['tenant_id'] ?? null,
            before:        $before,
            after:         $after,
            url:           $context['url'] ?? null,
            metadata:      $context['metadata'] ?? [],
            createdAt:     now(),
        );
    }

    public function toArray(): array
    {
        return [
            'event'          => $this->event,
            'auditable_type' => $this->auditableType,
            'auditable_id'   => $this->auditableId,
            'actor_type'     => $this->actorType,
            'actor_id'       => $this->actorId,
            'actor_ip'       => $this->actorIp,
            'tenant_id'      => $this->tenantId,
            'before'         => $this->before,
            'after'          => $this->after,
            'url'            => $this->url,
            'metadata'       => $this->metadata,
            'created_at'     => $this->createdAt,
        ];
    }
}
