<?php

namespace YourVendor\AuditTrail\Engine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ContextResolver
{
    public function resolve(): array
    {
        $actor  = $this->resolveActor();
        $tenant = $this->resolveTenant();

        return [
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id'   => $actor ? (string) $actor->getKey() : null,
            'actor_ip'   => $this->resolveIp(),
            'tenant_id'  => $tenant,
            'url'        => $this->resolveUrl(),
            'metadata'   => $this->resolveMetadata(),
        ];
    }

    protected function resolveActor(): ?Model
    {
        $resolver = config('audit.actor_resolver');

        if (is_callable($resolver)) {
            return $resolver();
        }

        try {
            return auth()->user();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveTenant(): ?string
    {
        $resolver = config('audit.tenant_resolver');

        if (is_callable($resolver)) {
            $result = $resolver();
            return $result !== null ? (string) $result : null;
        }

        return null;
    }

    protected function resolveIp(): ?string
    {
        if (!config('audit.metadata.capture_ip', true)) {
            return null;
        }

        try {
            return Request::ip();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveUrl(): ?string
    {
        if (!config('audit.metadata.capture_url', true)) {
            return null;
        }

        try {
            return Request::fullUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveMetadata(): array
    {
        $meta = [];

        if (config('audit.metadata.capture_user_agent', false)) {
            try {
                $meta['user_agent'] = Request::userAgent();
            } catch (\Throwable) {
                // CLI context — no request
            }
        }

        return $meta;
    }
}
