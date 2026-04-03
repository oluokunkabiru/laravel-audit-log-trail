<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Oluokunkabiru\AuditTrail\Facades\Auditor;
use Oluokunkabiru\AuditTrail\Models\AuditLog;
use Oluokunkabiru\AuditTrail\Traits\HasAuditTrail;

// ─── Test model ───────────────────────────────────────────────────────────────

class TestUser extends Model
{
    use HasAuditTrail;

    protected $table    = 'test_users';
    protected $guarded  = [];
    public $timestamps  = false;
}

class TestUserWithInclude extends Model
{
    use HasAuditTrail;

    protected $table        = 'test_users';
    protected $guarded      = [];
    public $timestamps      = false;
    protected array $auditInclude = ['name'];
}

class TestUserWithExclude extends Model
{
    use HasAuditTrail;

    protected $table        = 'test_users';
    protected $guarded      = [];
    public $timestamps      = false;
    protected array $auditExclude = ['secret'];
}

// ─── Setup ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Schema::create('test_users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('secret')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

// ─── Created event ────────────────────────────────────────────────────────────

it('logs a created event when a model is created', function () {
    TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);

    expect(AuditLog::count())->toBe(1);

    $log = AuditLog::first();
    expect($log->event)->toBe('created')
        ->and($log->after['name'])->toBe('Alice')
        ->and($log->before)->toBeEmpty();
});

// ─── Updated event ────────────────────────────────────────────────────────────

it('logs an updated event with a before/after diff', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    AuditLog::query()->delete(); // clear created log

    $user->update(['name' => 'Bob']);

    expect(AuditLog::count())->toBe(1);

    $log = AuditLog::first();
    expect($log->event)->toBe('updated')
        ->and($log->before['name'])->toBe('Alice')
        ->and($log->after['name'])->toBe('Bob')
        ->and($log->before)->not->toHaveKey('email') // email didn't change
        ->and($log->after)->not->toHaveKey('email');
});

it('does not log an update when no relevant attributes changed', function () {
    $user = TestUser::create(['name' => 'Alice']);
    AuditLog::query()->delete();

    // Save without actually changing anything
    $user->save();

    expect(AuditLog::count())->toBe(0);
});

// ─── Deleted event ────────────────────────────────────────────────────────────

it('logs a deleted event when a model is deleted', function () {
    $user = TestUser::create(['name' => 'Alice']);
    AuditLog::query()->delete();

    $user->delete();

    $log = AuditLog::first();
    expect($log->event)->toBe('deleted')
        ->and($log->before['name'])->toBe('Alice')
        ->and($log->after)->toBeEmpty();
});

// ─── Include/Exclude ──────────────────────────────────────────────────────────

it('only logs included fields when $auditInclude is set', function () {
    $user = TestUserWithInclude::create(['name' => 'Alice', 'email' => 'alice@example.com']);

    $log = AuditLog::first();
    expect($log->after)->toHaveKey('name')
        ->and($log->after)->not->toHaveKey('email');
});

it('excludes fields listed in $auditExclude', function () {
    $user = TestUserWithExclude::create(['name' => 'Alice', 'secret' => 's3cr3t']);

    $log = AuditLog::first();
    expect($log->after)->toHaveKey('name')
        ->and($log->after)->not->toHaveKey('secret');
});

// ─── Suppression ──────────────────────────────────────────────────────────────

it('does not log anything inside Auditor::suppress()', function () {
    Auditor::suppress(function () {
        TestUser::create(['name' => 'Alice']);
        TestUser::create(['name' => 'Bob']);
    });

    expect(AuditLog::count())->toBe(0);
});

it('resumes logging after suppression block ends', function () {
    Auditor::suppress(fn() => TestUser::create(['name' => 'Alice']));
    TestUser::create(['name' => 'Bob']);

    expect(AuditLog::count())->toBe(1)
        ->and(AuditLog::first()->after['name'])->toBe('Bob');
});

it('resumes logging even if suppression block throws', function () {
    try {
        Auditor::suppress(function () {
            TestUser::create(['name' => 'Alice']);
            throw new \RuntimeException('boom');
        });
    } catch (\RuntimeException) {}

    TestUser::create(['name' => 'Bob']);

    expect(AuditLog::count())->toBe(1);
});

it('does not log inside withoutAudit() on a model instance', function () {
    $user = TestUser::create(['name' => 'Alice']);
    AuditLog::query()->delete();

    $user->withoutAudit(fn() => $user->update(['name' => 'Silent Bob']));

    expect(AuditLog::count())->toBe(0);
});

// ─── Query scopes ─────────────────────────────────────────────────────────────

it('can query logs for a specific model instance', function () {
    $alice = TestUser::create(['name' => 'Alice']);
    $bob   = TestUser::create(['name' => 'Bob']);

    $logs = AuditLog::forModel($alice)->get();

    expect($logs)->toHaveCount(1)
        ->and($logs->first()->auditable_id)->toBe((string) $alice->id);
});

it('can filter logs by event type', function () {
    $user = TestUser::create(['name' => 'Alice']);
    $user->update(['name' => 'Alicia']);
    $user->delete();

    expect(AuditLog::event('created')->count())->toBe(1)
        ->and(AuditLog::event('updated')->count())->toBe(1)
        ->and(AuditLog::event('deleted')->count())->toBe(1);
});

it('can filter logs by a specific field', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'a@example.com']);
    AuditLog::query()->delete();

    $user->update(['name' => 'Bob']);
    $user->update(['email' => 'b@example.com']);

    expect(AuditLog::forField('name')->count())->toBe(1)
        ->and(AuditLog::forField('email')->count())->toBe(1);
});

// ─── AuditLog model helpers ───────────────────────────────────────────────────

it('can retrieve changed fields from a log entry', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'a@example.com']);
    AuditLog::query()->delete();

    $user->update(['name' => 'Bob', 'email' => 'b@example.com']);

    $log = AuditLog::first();
    expect($log->changedFields())->toContain('name', 'email');
});

it('can get old and new values from a log entry', function () {
    $user = TestUser::create(['name' => 'Alice']);
    AuditLog::query()->delete();

    $user->update(['name' => 'Bob']);

    $log = AuditLog::first();
    expect($log->oldValue('name'))->toBe('Alice')
        ->and($log->newValue('name'))->toBe('Bob');
});

// ─── Custom log ───────────────────────────────────────────────────────────────

it('can record a custom audit event via Auditor::log()', function () {
    $user = TestUser::create(['name' => 'Alice']);
    AuditLog::query()->delete();

    Auditor::log($user, 'password_reset', ['reset_method' => 'email']);

    $log = AuditLog::first();
    expect($log->event)->toBe('password_reset')
        ->and($log->before['reset_method'])->toBe('email');
});
