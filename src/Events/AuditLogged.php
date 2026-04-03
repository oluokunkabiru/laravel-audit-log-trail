<?php

namespace Oluokunkabiru\AuditTrail\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Oluokunkabiru\AuditTrail\Data\AuditEntry;

class AuditLogged
{
    use Dispatchable;

    public function __construct(public readonly AuditEntry $entry) {}
}
