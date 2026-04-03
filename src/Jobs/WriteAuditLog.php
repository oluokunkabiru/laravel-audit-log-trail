<?php

namespace Oluokunkabiru\AuditTrail\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Oluokunkabiru\AuditTrail\Data\AuditEntry;
use Oluokunkabiru\AuditTrail\Facades\Auditor;

class WriteAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public readonly AuditEntry $entry) {}

    public function handle(): void
    {
        Auditor::driver()->log($this->entry);
    }
}
