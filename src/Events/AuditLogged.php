<?php

namespace YourVendor\AuditTrail\Events;

use Illuminate\Foundation\Events\Dispatchable;
use YourVendor\AuditTrail\Data\AuditEntry;

class AuditLogged
{
    use Dispatchable;

    public function __construct(public readonly AuditEntry $entry) {}
}
