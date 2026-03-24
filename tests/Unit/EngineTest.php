<?php

use Datalumo\Laravel\Builder;
use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Tests\Fixtures\SearchableModel;
use Datalumo\PhpSdk\Datalumo;
use Datalumo\PhpSdk\DataObjects\ChatResponse;
use Datalumo\PhpSdk\DataObjects\Entry;
use Datalumo\PhpSdk\DataObjects\SearchResult;
use Datalumo\PhpSdk\DataObjects\SummaryResponse;
use Datalumo\PhpSdk\Resources\EntryResource;
use Datalumo\PhpSdk\Resources\IntegrationResource;

beforeEach(function () {
    $this->datalumo = Mockery::mock(Datalumo::class);
    $this->engine = new Engine($this->datalumo);
});

it('updates models via batch upsert', function () {
    $model = new SearchableModel(['id' => 1, 'title' => 'Test', 'body' => 'Content']);
    $model->exists = true;
    $models = new \Illuminate\Database\Eloquent\Collection([$model]);

    $entryResource = Mockery::mock(EntryResource::class);
    $entryResource->shouldReceive('batchUpsert')
        ->once()
        ->withArgs(function (array $entries) {
            return count($entries) === 1
                && $entries[0]['raw_text'] === "Test\n\nContent"
                && $entries[0]['title'] === 'Test'
                && $entries[0]['source_type'] === 'articles'
                && $entries[0]['source_id'] === '1';
        })
        ->andReturn(['created' => 1, 'updated' => 0]);

    $this->datalumo->shouldReceive('entries')
        ->with('col-test-123')
        ->once()
        ->andReturn($entryResource);

    $this->engine->update($models);
});

it('skips update for empty collection', function () {
    $this->datalumo->shouldNotReceive('entries');

    $this->engine->update(new \Illuminate\Database\Eloquent\Collection);
});

it('deletes models by source', function () {
    $model = new SearchableModel(['id' => 5, 'title' => 'Test', 'body' => 'Content']);
    $model->exists = true;
    $models = new \Illuminate\Database\Eloquent\Collection([$model]);

    $entryResource = Mockery::mock(EntryResource::class);
    $entryResource->shouldReceive('deleteBySource')
        ->with('articles', '5')
        ->once();

    $this->datalumo->shouldReceive('entries')
        ->with('col-test-123')
        ->once()
        ->andReturn($entryResource);

    $this->engine->delete($models);
});

it('skips delete for empty collection', function () {
    $this->datalumo->shouldNotReceive('entries');

    $this->engine->delete(new \Illuminate\Database\Eloquent\Collection);
});

it('performs a search via integration', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'test query', $this->engine);
    $builder->threshold(0.3);
    $builder->meta(['category' => 'php']);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('search')
        ->with('int-test-456', ['query' => 'test query', 'threshold' => 0.3, 'meta' => ['category' => 'php']])
        ->once()
        ->andReturn(new SearchResult(
            data: [],
            currentPage: 1,
            lastPage: 1,
            perPage: 15,
            total: 0,
        ));

    $this->datalumo->shouldReceive('integrations')
        ->once()
        ->andReturn($integrationResource);

    $result = $this->engine->search($builder);

    expect($result)->toBeInstanceOf(SearchResult::class);
});

it('paginates search results via integration', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'test', $this->engine);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('search')
        ->with('int-test-456', ['query' => 'test', 'per_page' => 10, 'page' => 2])
        ->once()
        ->andReturn(new SearchResult(
            data: [],
            currentPage: 2,
            lastPage: 3,
            perPage: 10,
            total: 30,
        ));

    $this->datalumo->shouldReceive('integrations')
        ->once()
        ->andReturn($integrationResource);

    $result = $this->engine->paginate($builder, 10, 2);

    expect($result->currentPage)->toBe(2);
});

it('summarises via integration', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'explain php', $this->engine);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('summarise')
        ->with('int-test-456', ['query' => 'explain php'])
        ->once()
        ->andReturn(new SummaryResponse(
            summary: 'PHP is a language.',
            references: [],
            data: [],
        ));

    $this->datalumo->shouldReceive('integrations')
        ->once()
        ->andReturn($integrationResource);

    $result = $this->engine->summarise($builder);

    expect($result)->toBeInstanceOf(SummaryResponse::class)
        ->and($result->summary)->toBe('PHP is a language.');
});

it('summarises with format and locale', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'explain php', $this->engine);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('summarise')
        ->with('int-test-456', ['query' => 'explain php', 'format' => 'html', 'locale' => 'nl'])
        ->once()
        ->andReturn(new SummaryResponse(summary: 'PHP', references: [], data: []));

    $this->datalumo->shouldReceive('integrations')->andReturn($integrationResource);

    $this->engine->summarise($builder, 'html', 'nl');
});

it('chats via integration', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'what is PHP?', $this->engine);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('chat')
        ->with('int-test-456', ['message' => 'what is PHP?'])
        ->once()
        ->andReturn(new ChatResponse(
            conversationId: 'conv-1',
            message: 'PHP is a programming language.',
        ));

    $this->datalumo->shouldReceive('integrations')
        ->once()
        ->andReturn($integrationResource);

    $result = $this->engine->chat($builder);

    expect($result)->toBeInstanceOf(ChatResponse::class)
        ->and($result->conversationId)->toBe('conv-1');
});

it('chats with an existing conversation', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'tell me more', $this->engine);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('chat')
        ->with('int-test-456', ['message' => 'tell me more', 'conversation_id' => 'conv-1'])
        ->once()
        ->andReturn(new ChatResponse(conversationId: 'conv-1', message: 'More info.'));

    $this->datalumo->shouldReceive('integrations')->andReturn($integrationResource);

    $result = $this->engine->chat($builder, 'conv-1');

    expect($result->message)->toBe('More info.');
});

it('maps results to eloquent models', function () {
    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('searchableSourceType')->andReturn('articles');
    $model->shouldReceive('newCollection')->andReturnUsing(fn ($items = []) => new \Illuminate\Database\Eloquent\Collection($items));

    $existingModel = new SearchableModel(['id' => 1, 'title' => 'Found']);
    $existingModel->exists = true;

    $model->shouldReceive('getScoutModelsByIds')
        ->once()
        ->andReturn(new \Illuminate\Database\Eloquent\Collection([$existingModel]));

    $builder = new Builder($model, 'test', $this->engine);

    $entry = new Entry(
        id: 'entry-1',
        collectionId: 'col-1',
        title: 'Found',
        rawText: 'Content',
        meta: null,

        sourceUrl: null,
        sourceType: 'articles',
        sourceId: '1',
        createdAt: '2026-01-01T00:00:00Z',
        updatedAt: '2026-01-01T00:00:00Z',
    );

    $results = new SearchResult(
        data: [$entry],
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 1,
    );

    $mapped = $this->engine->mapToModels($results, $builder);

    expect($mapped)->toHaveCount(1)
        ->and($mapped->first()->title)->toBe('Found');
});

it('returns empty collection when no results', function () {
    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('newCollection')->andReturn(new \Illuminate\Database\Eloquent\Collection);

    $builder = new Builder($model, 'test', $this->engine);

    $results = new SearchResult(
        data: [],
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    );

    $mapped = $this->engine->mapToModels($results, $builder);

    expect($mapped)->toBeEmpty();
});
