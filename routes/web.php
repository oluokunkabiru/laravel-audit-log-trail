<?php

use Illuminate\Support\Facades\Route;
use Oluokunkabiru\AuditTrail\Http\Livewire\Dashboard;

$prefix     = config('audit.route_prefix', 'audit-trail');
$middleware = config('audit.route_middleware', ['web']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('audit-trail.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');
    });
