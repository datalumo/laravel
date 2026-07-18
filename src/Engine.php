<?php

namespace Datalumo\Laravel;

use Datalumo\Client;
use Datalumo\Data\SearchResult;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Engine
{
    public function __construct(private readonly Client $client) {}

    public function update(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->groupBy(fn (Model $model) => $model->datalumoSource())
            ->each(function (Collection $group, string $source): void {
                $chunks = $group->chunk((int) config('datalumo.chunk_size', 50));

                foreach ($chunks as $chunk) {
                    $pages = $chunk->map(function (Model $model) {
                        $payload = $model->toDatalumoArray();

                        if (! isset($payload['external_id']) || $payload['external_id'] === '' || $payload['external_id'] === null) {
                            $payload['external_id'] = (string) $model->getDatalumoKey();
                        }

                        return $payload;
                    })->values()->all();

                    $this->client->pages($source)->batchUpsert($pages);
                }
            });
    }

    public function delete(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->each(function (Model $model): void {
            $externalId = (string) ($model->toDatalumoArray()['external_id'] ?? $model->getDatalumoKey());

            $this->client->pages($model->datalumoSource())->delete($externalId);
        });
    }

    public function search(Builder $builder): SearchResult
    {
        $params = array_filter([
            'query' => $builder->query,
            'filters' => $builder->filters ?: null,
            'limit' => $builder->limit ?? config('datalumo.search.limit', 15),
            'session_id' => $builder->sessionId,
            'sort' => $builder->sort,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->client->widgets($builder->model->datalumoWidgetPublicId())->search($params);
    }

    public function mapToModels(SearchResult $result, Builder $builder): EloquentCollection
    {
        /** @var Model $model */
        $model = $builder->model;
        $keyName = $model->getKeyName();

        $externalIds = collect($result->data)
            ->map(fn ($hit) => $hit->externalId)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->values()
            ->all();

        if ($externalIds === []) {
            return $model->newCollection();
        }

        $query = in_array(SoftDeletes::class, class_uses_recursive($model), true)
            ? $model->newQuery()->withTrashed()
            : $model->newQuery();

        $models = $query->whereIn($keyName, $externalIds)->get()->keyBy($keyName);

        $ordered = new EloquentCollection;

        foreach ($externalIds as $id) {
            if ($models->has($id)) {
                $ordered->push($models->get($id));
            } elseif ($models->has((int) $id) && (string) (int) $id === (string) $id) {
                $ordered->push($models->get((int) $id));
            }
        }

        return $ordered;
    }
}
