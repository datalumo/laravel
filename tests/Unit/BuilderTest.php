<?php

use Datalumo\Laravel\Builder;
use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Tests\Fixtures\SearchableModel;
use Datalumo\PhpSdk\DataObjects\ChatResponse;
use Datalumo\PhpSdk\DataObjects\Entry;
use Datalumo\PhpSdk\DataObjects\SearchResult;
use Datalumo\PhpSdk\DataObjects\SummaryResponse;

beforeEach(function () {
    $this->engine = Mockery::mock(Engine::class);
    $this->model = new SearchableModel;
});

it('sets query on construction', function () {
    $builder = new Builder($this->model, 'test query', $this->engine);

    expect($builder->query)->toBe('test query');
});

it('sets threshold fluently', function () {
    $builder = new Builder($this->model, 'test', $this->engine);

    $result = $builder->threshold(0.5);

    expect($result)->toBe($builder)
        ->and($builder->threshold)->toBe(0.5);
});

it('sets meta from array', function () {
    $builder = new Builder($this->model, 'test', $this->engine);

    $builder->meta(['author' => 'John']);

    expect($builder->meta)->toBe(['author' => 'John']);
});

it('calls engine search and maps to models via get()', function () {
    $builder = new Builder($this->model, 'test', $this->engine);
    $paginatedResponse = new SearchResult(data: [], currentPage: 1, lastPage: 1, perPage: 15, total: 0);
    $collection = new \Illuminate\Database\Eloquent\Collection;

    $this->engine->shouldReceive('search')->with($builder)->once()->andReturn($paginatedResponse);
    $this->engine->shouldReceive('mapToModels')->with($paginatedResponse, $builder)->once()->andReturn($collection);

    $result = $builder->get();

    expect($result)->toBe($collection);
});

it('returns raw results without model mapping', function () {
    $builder = new Builder($this->model, 'test', $this->engine);

    $entry = new Entry(
        id: 'e-1', collectionId: 'c-1', title: 'T', rawText: 'text',
        meta: null, sourceUrl: null, sourceType: 'articles',
        sourceId: '1', createdAt: '2026-01-01T00:00:00Z', updatedAt: '2026-01-01T00:00:00Z',
    );

    $this->engine->shouldReceive('search')
        ->with($builder)
        ->once()
        ->andReturn(new SearchResult(data: [$entry], currentPage: 1, lastPage: 1, perPage: 15, total: 1));

    $result = $builder->raw();

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(Entry::class);
});

it('delegates summarise to engine', function () {
    $builder = new Builder($this->model, 'explain this', $this->engine);

    $summary = new SummaryResponse(summary: 'Summary', references: [], data: []);

    $this->engine->shouldReceive('summarise')
        ->with($builder, 'html', 'nl')
        ->once()
        ->andReturn($summary);

    $result = $builder->summarise('html', 'nl');

    expect($result)->toBe($summary);
});

it('delegates chat to engine', function () {
    $builder = new Builder($this->model, 'hello', $this->engine);

    $chat = new ChatResponse(conversationId: 'conv-1', message: 'Hi!');

    $this->engine->shouldReceive('chat')
        ->with($builder, 'conv-1')
        ->once()
        ->andReturn($chat);

    $result = $builder->chat('conv-1');

    expect($result)->toBe($chat);
});

it('chains threshold and meta fluently', function () {
    $builder = new Builder($this->model, 'test', $this->engine);

    $result = $builder->threshold(0.4)->meta(['author' => 'John']);

    expect($result)->toBe($builder)
        ->and($builder->threshold)->toBe(0.4)
        ->and($builder->meta)->toBe(['author' => 'John']);
});
