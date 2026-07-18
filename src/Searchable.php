<?php

namespace Datalumo\Laravel;

use Datalumo\Laravel\Jobs\MakeSearchable;
use Datalumo\Laravel\Jobs\RemoveFromSearch;
use Datalumo\Laravel\Support\WidgetKey;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait Searchable
{
    public static function bootSearchable(): void
    {
        static::observe(ModelObserver::class);

        if (! EloquentCollection::hasMacro('searchable')) {
            EloquentCollection::macro('searchable', function () {
                /** @var EloquentCollection $this */
                if ($this->isEmpty()) {
                    return;
                }

                $this->first()->queueMakeSearchable($this);
            });
        }

        if (! EloquentCollection::hasMacro('unsearchable')) {
            EloquentCollection::macro('unsearchable', function () {
                /** @var EloquentCollection $this */
                if ($this->isEmpty()) {
                    return;
                }

                $this->first()->queueRemoveFromSearch($this);
            });
        }
    }

    public static function datalumoSearch(string $query = ''): Builder
    {
        return app(Builder::class, [
            'model' => new static,
            'query' => $query,
        ]);
    }

    /** @return array<string, mixed> */
    abstract public function toDatalumoArray(): array;

    public function datalumoSource(): string
    {
        $map = config('datalumo.model_sources', []);
        $class = static::class;

        if (isset($map[$class])) {
            return (string) $map[$class];
        }

        return (string) config('datalumo.source');
    }

    public function datalumoWidget(): string
    {
        return WidgetKey::publicId(WidgetKey::search());
    }

    public function datalumoWidgetKey(): string
    {
        return WidgetKey::embedKey(WidgetKey::search());
    }

    public function datalumoWidgetPublicId(): string
    {
        return $this->datalumoWidget();
    }

    public function datalumoChatWidgetKey(): string
    {
        return WidgetKey::embedKey(WidgetKey::chat());
    }

    public function datalumoChatWidgetPublicId(): string
    {
        return WidgetKey::publicId(WidgetKey::chat());
    }

    public function shouldBeSearchable(): bool
    {
        return true;
    }

    public function getDatalumoKey(): mixed
    {
        return $this->getKey();
    }

    public function datalumoHash(): string
    {
        return md5(serialize($this->toDatalumoArray()));
    }

    public function searchable(): void
    {
        $this->newCollection([$this])->searchable();
    }

    public function unsearchable(): void
    {
        $this->newCollection([$this])->unsearchable();
    }

    public function queueMakeSearchable(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        if (config('datalumo.queue')) {
            dispatch(new MakeSearchable($models))
                ->onConnection(config('datalumo.queue_connection'))
                ->onQueue(config('datalumo.queue_name'));

            return;
        }

        $models->first()->searchableUsing()->update($models);
    }

    public function queueRemoveFromSearch(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        if (config('datalumo.queue')) {
            dispatch(new RemoveFromSearch($models))
                ->onConnection(config('datalumo.queue_connection'))
                ->onQueue(config('datalumo.queue_name'));

            return;
        }

        $models->first()->searchableUsing()->delete($models);
    }

    public function searchableUsing(): Engine
    {
        return app(Engine::class);
    }
}
