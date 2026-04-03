<?php

namespace YourVendor\AuditTrail\Drivers\Contracts;

use YourVendor\AuditTrail\Data\AuditEntry;

interface AuditDriver
{
    /**
     * Persist an audit entry.
     */
    public function log(AuditEntry $entry): void;

    /**
     * Delete entries older than the given number of days.
     * Returns the number of records deleted.
     */
    public function prune(int $keepDays): int;
}
