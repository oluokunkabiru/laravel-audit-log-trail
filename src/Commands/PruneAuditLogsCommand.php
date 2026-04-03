<?php

namespace Oluokunkabiru\AuditTrail\Commands;

use Illuminate\Console\Command;
use Oluokunkabiru\AuditTrail\Facades\Auditor;

class PruneAuditLogsCommand extends Command
{
    protected $signature   = 'audit:prune {--days= : Number of days of logs to keep (overrides config)}';
    protected $description = 'Delete audit logs older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('audit.prune.keep_days', 365));

        $this->info("Pruning audit logs older than {$days} days...");

        $deleted = Auditor::driver()->prune($days);

        $this->info("Done. {$deleted} record(s) deleted.");

        return self::SUCCESS;
    }
}
