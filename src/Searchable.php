<?php

namespace Datalumo\Laravel;

use Datalumo\Laravel\Jobs\MakeSearchable;
use Datalumo\Laravel\Jobs\RemoveFromSearch;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @mixin Model
 */
trait Searchable
{
    public static function bootSearchable(): void
    {
        static::registerModelEvent('saved', fn (Model $model) => (new ModelObserver)->saved($model));
        static::registerModelEvent('deleted', fn (Model $model) => (new ModelObserver)->deleted($model));
        static::registerModelEvent('forceDeleted', fn (Model $model) => (new ModelObserver)->forceDeleted($model));
        static::registerModelEvent('restored', fn (Model $model) => (new ModelObserver)->restored($model));

        if (! EloquentCollection::hasMacro('searchable')) {
            EloquentCollection::macro('searchable', function () {
                if ($this->isEmpty()) {
                    return;
                }

                $this->first()->queueMakeSearchable($this);
            });
        }

        if (! EloquentCollection::hasMacro('unsearchable')) {
            EloquentCollection::macro('unsearchable', function () {
                if ($this->isEmpty()) {
                    return;
                }

                $this->first()->queueRemoveFromSearch($this);
            });
        }
    }

    /**
     * Perform a search against the model's Datalumo integration.
     */
    public static function search(string $query = ''): Builder
    {
        return app(Builder::class, [
            'model' => new static,
            'query' => $query,
        ]);
    }

    /**
     * Dispatch the job to make the given models searchable.
     */
    public function queueMakeSearchable(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        if (config('datalumo.queue')) {
            dispatch(new MakeSearchable($models))
                ->onConnection(config('datalumo.queue_connection'))
                ->onQueue(config('datalumo.queue_name'));
        } else {
            $models->first()->searchableUsing()->update($models);
        }
    }

    /**
     * Dispatch the job to remove the given models from search.
     */
    public function queueRemoveFromSearch(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        if (config('datalumo.queue')) {
            dispatch(new RemoveFromSearch($models))
                ->onConnection(config('datalumo.queue_connection'))
                ->onQueue(config('datalumo.queue_name'));
        } else {
            $models->first()->searchableUsing()->delete($models);
        }
    }

    /**
     * Make this model instance searchable.
     */
    public function searchable(): void
    {
        $this->newCollection([$this])->searchable();
    }

    /**
     * Remove this model instance from search.
     */
    public function unsearchable(): void
    {
        $this->newCollection([$this])->unsearchable();
    }

    /**
     * The Datalumo collection ID this model syncs entries to.
     */
    public function searchableCollectionId(): string
    {
        return $this->datalumoCollectionId;
    }

    /**
     * The Datalumo integration ID used for search, summarise, and chat.
     */
    public function searchableIntegrationId(): string
    {
        return $this->datalumoIntegrationId;
    }

    /**
     * The source type used to identify this model in Datalumo.
     */
    public function searchableSourceType(): string
    {
        return $this->getTable();
    }

    /**
     * The text content to index in Datalumo.
     */
    abstract public function toSearchableText(): string;

    /**
     * The title for the Datalumo entry.
     */
    public function toSearchableTitle(): ?string
    {
        return null;
    }

    /**
     * Metadata to attach to the Datalumo entry.
     */
    public function toSearchableMeta(): ?array
    {
        return null;
    }

    /**
     * Searchable metadata to attach to the Datalumo entry.
     */
    public function toSearchableSearchableMeta(): ?array
    {
        return null;
    }

    /**
     * The source URL for the Datalumo entry.
     */
    public function toSearchableSourceUrl(): ?string
    {
        return null;
    }

    /**
     * A deterministic hash of the searchable representation.
     *
     * Used by `datalumo:reconcile` to skip unchanged rows. Override to
     * include or exclude additional fields.
     */
    public function searchableHash(): string
    {
        return md5(serialize([
            $this->toSearchableText(),
            $this->toSearchableTitle(),
            $this->toSearchableMeta(),
            $this->toSearchableSearchableMeta(),
            $this->toSearchableSourceUrl(),
            $this->searchableSourceType(),
            (string) $this->getScoutKey(),
        ]));
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * Get the key used to identify the model in Datalumo.
     */
    public function getScoutKey(): mixed
    {
        return $this->getKey();
    }

    /**
     * Get the key name used to identify the model in Datalumo.
     */
    public function getScoutKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get models by their scout keys.
     *
     * @param array<int, string> $ids
     */
    public function getScoutModelsByIds(Builder $builder, array $ids): EloquentCollection
    {
        $query = in_array(SoftDeletes::class, class_uses_recursive($this))
            ? $this->withTrashed()
            : $this->newQuery();

        return $query->whereIn($this->getScoutKeyName(), $ids)->get();
    }

    /**
     * Get the engine instance.
     */
    public function searchableUsing(): Engine
    {
        return app(Engine::class);
    }
}
