<?php

namespace Oluokunkabiru\AuditTrail\Engine;

use Illuminate\Database\Eloquent\Model;

class DiffEngine
{
    /**
     * Global attribute keys always excluded from diffs.
     */
    protected array $globalExclude = [];

    public function __construct(array $globalExclude = [])
    {
        $this->globalExclude = $globalExclude;
    }

    /**
     * Compute the before/after diff for a model event.
     *
     * @return array{before: array, after: array}
     */
    public function compute(Model $model, string $event): array
    {
        $exclude = array_unique(array_merge(
            $this->globalExclude,
            $model->auditExclude ?? [],
        ));

        $include = $model->auditInclude ?? [];

        return match ($event) {
            'created'  => $this->forCreated($model, $include, $exclude),
            'updated'  => $this->forUpdated($model, $include, $exclude),
            'deleted'  => $this->forDeleted($model, $include, $exclude),
            'restored' => $this->forRestored($model, $include, $exclude),
            default    => ['before' => [], 'after' => []],
        };
    }

    protected function forCreated(Model $model, array $include, array $exclude): array
    {
        $after = $this->filterAttributes(
            $model->getAttributes(),
            $include,
            $exclude,
        );

        return ['before' => [], 'after' => $after];
    }

    protected function forUpdated(Model $model, array $include, array $exclude): array
    {
        $dirty = $model->getDirty();

        if (empty($dirty)) {
            return ['before' => [], 'after' => []];
        }

        $before = $this->filterAttributes(
            array_intersect_key($model->getOriginal(), $dirty),
            $include,
            $exclude,
        );

        $after = $this->filterAttributes(
            $dirty,
            $include,
            $exclude,
        );

        return ['before' => $before, 'after' => $after];
    }

    protected function forDeleted(Model $model, array $include, array $exclude): array
    {
        $before = $this->filterAttributes(
            $model->getAttributes(),
            $include,
            $exclude,
        );

        return ['before' => $before, 'after' => []];
    }

    protected function forRestored(Model $model, array $include, array $exclude): array
    {
        // On restore, the model now has deleted_at = null
        $after = $this->filterAttributes(
            $model->getAttributes(),
            $include,
            $exclude,
        );

        return ['before' => [], 'after' => $after];
    }

    /**
     * Apply include/exclude filters to an attributes array.
     * Include list takes priority over exclude — if $include is non-empty,
     * only those keys are kept (minus $exclude).
     */
    protected function filterAttributes(array $attributes, array $include, array $exclude): array
    {
        if (!empty($include)) {
            $attributes = array_intersect_key($attributes, array_flip($include));
        }

        foreach ($exclude as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }
}
