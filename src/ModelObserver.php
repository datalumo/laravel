<?php

namespace Datalumo\Laravel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelObserver
{
    public function saved(Model $model): void
    {
        if (! $this->usesSearchable($model)) {
            return;
        }

        if ($model->shouldBeSearchable()) {
            $model->searchable();

            return;
        }

        $model->unsearchable();
    }

    public function deleted(Model $model): void
    {
        if (! $this->usesSearchable($model)) {
            return;
        }

        if ($this->usesSoftDelete($model) && ! $model->isForceDeleting()) {
            $this->saved($model);

            return;
        }

        $model->unsearchable();
    }

    public function forceDeleted(Model $model): void
    {
        if (! $this->usesSearchable($model)) {
            return;
        }

        $model->unsearchable();
    }

    public function restored(Model $model): void
    {
        $this->saved($model);
    }

    private function usesSearchable(Model $model): bool
    {
        return in_array(Searchable::class, class_uses_recursive($model), true);
    }

    private function usesSoftDelete(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }
}
