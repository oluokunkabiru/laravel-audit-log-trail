<?php

use Illuminate\Database\Eloquent\Model;
use Oluokunkabiru\AuditTrail\Engine\DiffEngine;

// ─── Minimal fake model for unit tests ───────────────────────────────────────

class FakeModel extends Model
{
    protected $guarded = [];
    public $exists     = true;

    public function __construct(array $attributes = [], array $original = [], array $dirty = [])
    {
        parent::__construct($attributes);
        $this->syncOriginal();

        // Override original and dirty for testing
        if (!empty($original)) {
            foreach ($original as $key => $value) {
                $this->original[$key] = $value;
            }
        }

        if (!empty($dirty)) {
            foreach ($dirty as $key => $value) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public function setOriginalForTest(array $original): void
    {
        $this->original = $original;
    }
}

// ─── Tests ───────────────────────────────────────────────────────────────────

describe('DiffEngine', function () {

    it('computes a full after diff for created events', function () {
        $engine = new DiffEngine();
        $model  = new FakeModel(['name' => 'Alice', 'email' => 'a@example.com']);

        $diff = $engine->compute($model, 'created');

        expect($diff['before'])->toBeEmpty()
            ->and($diff['after'])->toMatchArray(['name' => 'Alice', 'email' => 'a@example.com']);
    });

    it('computes only changed attributes for updated events', function () {
        $engine = new DiffEngine();
        $model  = new FakeModel();
        $model->setOriginalForTest(['name' => 'Alice', 'email' => 'a@example.com']);
        $model->name = 'Bob'; // dirty

        $diff = $engine->compute($model, 'updated');

        expect($diff['before'])->toMatchArray(['name' => 'Alice'])
            ->and($diff['after'])->toMatchArray(['name' => 'Bob'])
            ->and($diff['before'])->not->toHaveKey('email')
            ->and($diff['after'])->not->toHaveKey('email');
    });

    it('computes a full before diff for deleted events', function () {
        $engine = new DiffEngine();
        $model  = new FakeModel(['name' => 'Alice', 'email' => 'a@example.com']);

        $diff = $engine->compute($model, 'deleted');

        expect($diff['after'])->toBeEmpty()
            ->and($diff['before'])->toMatchArray(['name' => 'Alice', 'email' => 'a@example.com']);
    });

    it('respects the $auditInclude list', function () {
        $engine = new DiffEngine();
        $model  = new FakeModel(['name' => 'Alice', 'email' => 'a@example.com', 'role' => 'admin']);
        $model->auditInclude = ['name'];

        $diff = $engine->compute($model, 'created');

        expect($diff['after'])->toHaveKey('name')
            ->and($diff['after'])->not->toHaveKey('email')
            ->and($diff['after'])->not->toHaveKey('role');
    });

    it('respects the $auditExclude list', function () {
        $engine = new DiffEngine();
        $model  = new FakeModel(['name' => 'Alice', 'password' => 'secret']);
        $model->auditExclude = ['password'];

        $diff = $engine->compute($model, 'created');

        expect($diff['after'])->toHaveKey('name')
            ->and($diff['after'])->not->toHaveKey('password');
    });

    it('applies global exclude from config', function () {
        $engine = new DiffEngine(globalExclude: ['remember_token']);
        $model  = new FakeModel(['name' => 'Alice', 'remember_token' => 'abc123']);

        $diff = $engine->compute($model, 'created');

        expect($diff['after'])->toHaveKey('name')
            ->and($diff['after'])->not->toHaveKey('remember_token');
    });

    it('returns empty diff for unknown events', function () {
        $engine = new DiffEngine();
        $model  = new FakeModel(['name' => 'Alice']);

        $diff = $engine->compute($model, 'unknown_event');

        expect($diff['before'])->toBeEmpty()
            ->and($diff['after'])->toBeEmpty();
    });
});
