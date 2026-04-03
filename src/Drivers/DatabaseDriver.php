<?php

namespace Oluokunkabiru\AuditTrail\Drivers;

use Illuminate\Support\Str;
use Oluokunkabiru\AuditTrail\Data\AuditEntry;
use Oluokunkabiru\AuditTrail\Drivers\Contracts\AuditDriver;
use Oluokunkabiru\AuditTrail\Models\AuditLog;

class DatabaseDriver implements AuditDriver
{
    public function log(AuditEntry $entry): void
    {
        AuditLog::create([
            'id'             => Str::ulid()->toBase32(),
            ...$entry->toArray(),
        ]);
    }

    public function prune(int $keepDays): int
    {
        return AuditLog::query()
            ->where('created_at', '<', now()->subDays($keepDays))
            ->delete();
    }
}
