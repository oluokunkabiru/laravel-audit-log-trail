<?php

namespace YourVendor\AuditTrail\Drivers;

use Illuminate\Support\Str;
use YourVendor\AuditTrail\Data\AuditEntry;
use YourVendor\AuditTrail\Drivers\Contracts\AuditDriver;
use YourVendor\AuditTrail\Models\AuditLog;

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
