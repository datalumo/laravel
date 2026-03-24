<?php

namespace Datalumo\Laravel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelObserver
{
    public function saved(Model $model): void
    {
        if (! $model->shouldBeSearchable()) {
            if ($model->wasChanged()) {
                $model->unsearchable();
            }

            return;
        }

        $model->searchable();
    }

    public function deleted(Model $model): void
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && ! $model->isForceDeleting()) {
            $this->saved($model);

            return;
        }

        $model->unsearchable();
    }

    public function forceDeleted(Model $model): void
    {
        $model->unsearchable();
    }

    public function restored(Model $model): void
    {
        $this->saved($model);
    }
}
