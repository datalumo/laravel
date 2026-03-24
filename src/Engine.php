<?php

namespace Datalumo\Laravel;

use Datalumo\PhpSdk\Datalumo;
use Datalumo\PhpSdk\DataObjects\ChatResponse;
use Datalumo\PhpSdk\DataObjects\Entry;
use Datalumo\PhpSdk\DataObjects\PaginatedResponse;
use Datalumo\PhpSdk\DataObjects\SearchResult;
use Datalumo\PhpSdk\DataObjects\StreamResponse;
use Datalumo\PhpSdk\DataObjects\SummaryResponse;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

class Engine
{
    public function __construct(private readonly Datalumo $datalumo) {}

    public function update(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $first = $models->first();
        $collectionId = $first->searchableCollectionId();

        $entries = $models->map(function (Model $model) {
            return array_filter([
                'raw_text' => $model->toSearchableText(),
                'title' => $model->toSearchableTitle(),
                'meta' => $model->toSearchableMeta(),
                'source_url' => $model->toSearchableSourceUrl(),
                'source_type' => $model->searchableSourceType(),
                'source_id' => (string) $model->getScoutKey(),
            ], fn ($value) => $value !== null && $value !== '' && $value !== []);
        })->all();

        $this->datalumo->entries($collectionId)->batchUpsert($entries);
    }

    public function delete(EloquentCollection $models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $first = $models->first();
        $collectionId = $first->searchableCollectionId();
        $sourceType = $first->searchableSourceType();

        $models->each(function (Model $model) use ($collectionId, $sourceType) {
            $this->datalumo->entries($collectionId)->deleteBySource(
                $sourceType,
                (string) $model->getScoutKey(),
            );
        });
    }

    public function search(Builder $builder): SearchResult
    {
        return $this->datalumo->integrations()->search(
            $builder->model->searchableIntegrationId(),
            $this->buildSearchParams($builder),
        );
    }

    public function paginate(Builder $builder, int $perPage, int $page): SearchResult
    {
        $params = $this->buildSearchParams($builder);
        $params['per_page'] = $perPage;
        $params['page'] = $page;

        return $this->datalumo->integrations()->search(
            $builder->model->searchableIntegrationId(),
            $params,
        );
    }

    public function summarise(Builder $builder, ?string $format = null, ?string $locale = null): SummaryResponse
    {
        $params = array_filter([
            'query' => $builder->query,
            'threshold' => $builder->threshold,
            'format' => $format,
            'locale' => $locale,
        ]);

        return $this->datalumo->integrations()->summarise(
            $builder->model->searchableIntegrationId(),
            $params,
        );
    }

    public function chat(Builder $builder, ?string $conversationId = null): ChatResponse
    {
        $params = array_filter([
            'message' => $builder->query,
            'conversation_id' => $conversationId,
        ]);

        return $this->datalumo->integrations()->chat(
            $builder->model->searchableIntegrationId(),
            $params,
        );
    }

    public function streamSummarise(Builder $builder, ?string $format = null, ?string $locale = null): StreamResponse
    {
        $params = array_filter([
            'query' => $builder->query,
            'threshold' => $builder->threshold,
            'format' => $format,
            'locale' => $locale,
        ]);

        return $this->datalumo->integrations()->streamSummarise(
            $builder->model->searchableIntegrationId(),
            $params,
        );
    }

    public function streamChat(Builder $builder, ?string $conversationId = null): StreamResponse
    {
        $params = array_filter([
            'message' => $builder->query,
            'conversation_id' => $conversationId,
        ]);

        return $this->datalumo->integrations()->streamChat(
            $builder->model->searchableIntegrationId(),
            $params,
        );
    }

    public function mapToModels(SearchResult $results, Builder $builder): EloquentCollection
    {
        if (empty($results->data)) {
            return $builder->model->newCollection();
        }

        $sourceType = $builder->model->searchableSourceType();

        $ids = collect($results->data)
            ->filter(fn (Entry $entry) => $entry->sourceType === $sourceType)
            ->map(fn (Entry $entry) => $entry->sourceId)
            ->values()
            ->all();

        $models = $builder->model->getScoutModelsByIds($builder, $ids)
            ->keyBy(fn (Model $model) => (string) $model->getScoutKey());

        return $builder->model->newCollection(
            collect($ids)->map(fn ($id) => $models->get($id))->filter()->values()->all()
        );
    }

    private function buildSearchParams(Builder $builder): array
    {
        return array_filter([
            'query' => $builder->query,
            'threshold' => $builder->threshold,
            'meta' => $builder->meta,
        ]);
    }
}
