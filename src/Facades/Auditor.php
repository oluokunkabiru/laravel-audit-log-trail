<?php

namespace Oluokunkabiru\AuditTrail\Facades;

use Illuminate\Support\Facades\Facade;
use Oluokunkabiru\AuditTrail\AuditManager;

/**
 * @method static void    record(\Illuminate\Database\Eloquent\Model $model, string $event)
 * @method static void    log(\Illuminate\Database\Eloquent\Model $model, string $event, array $before = [], array $after = [])
 * @method static mixed   suppress(\Closure $callback)
 * @method static bool    isSuppressed()
 * @method static \Oluokunkabiru\AuditTrail\Drivers\Contracts\AuditDriver driver()
 *
 * @see AuditManager
 */
class Auditor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuditManager::class;
    }
}
