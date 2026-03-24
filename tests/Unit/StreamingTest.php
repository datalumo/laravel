<?php

use Datalumo\Laravel\Builder;
use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Tests\Fixtures\SearchableModel;
use Datalumo\PhpSdk\Datalumo;
use Datalumo\PhpSdk\DataObjects\StreamResponse;
use Datalumo\PhpSdk\Resources\IntegrationResource;

beforeEach(function () {
    $this->datalumo = Mockery::mock(Datalumo::class);
    $this->engine = new Engine($this->datalumo);
});

it('streams summarise via integration', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'explain this', $this->engine);
    $streamResponse = Mockery::mock(StreamResponse::class);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('streamSummarise')
        ->with('int-test-456', ['query' => 'explain this'])
        ->once()
        ->andReturn($streamResponse);

    $this->datalumo->shouldReceive('integrations')->andReturn($integrationResource);

    $result = $this->engine->streamSummarise($builder);

    expect($result)->toBe($streamResponse);
});

it('streams summarise with format and locale', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'explain this', $this->engine);
    $streamResponse = Mockery::mock(StreamResponse::class);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('streamSummarise')
        ->with('int-test-456', ['query' => 'explain this', 'format' => 'html', 'locale' => 'nl'])
        ->once()
        ->andReturn($streamResponse);

    $this->datalumo->shouldReceive('integrations')->andReturn($integrationResource);

    $this->engine->streamSummarise($builder, 'html', 'nl');
});

it('streams chat via integration', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'hello', $this->engine);
    $streamResponse = Mockery::mock(StreamResponse::class);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('streamChat')
        ->with('int-test-456', ['message' => 'hello'])
        ->once()
        ->andReturn($streamResponse);

    $this->datalumo->shouldReceive('integrations')->andReturn($integrationResource);

    $result = $this->engine->streamChat($builder);

    expect($result)->toBe($streamResponse);
});

it('streams chat with conversation id', function () {
    $model = new SearchableModel;
    $builder = new Builder($model, 'tell me more', $this->engine);
    $streamResponse = Mockery::mock(StreamResponse::class);

    $integrationResource = Mockery::mock(IntegrationResource::class);
    $integrationResource->shouldReceive('streamChat')
        ->with('int-test-456', ['message' => 'tell me more', 'conversation_id' => 'conv-1'])
        ->once()
        ->andReturn($streamResponse);

    $this->datalumo->shouldReceive('integrations')->andReturn($integrationResource);

    $this->engine->streamChat($builder, 'conv-1');
});

it('delegates streamSummarise from builder to engine', function () {
    $engine = Mockery::mock(Engine::class);
    $model = new SearchableModel;
    $builder = new Builder($model, 'test', $engine);
    $streamResponse = Mockery::mock(StreamResponse::class);

    $engine->shouldReceive('streamSummarise')
        ->with($builder, 'html', 'nl')
        ->once()
        ->andReturn($streamResponse);

    $result = $builder->streamSummarise('html', 'nl');

    expect($result)->toBe($streamResponse);
});

it('delegates streamChat from builder to engine', function () {
    $engine = Mockery::mock(Engine::class);
    $model = new SearchableModel;
    $builder = new Builder($model, 'hello', $engine);
    $streamResponse = Mockery::mock(StreamResponse::class);

    $engine->shouldReceive('streamChat')
        ->with($builder, 'conv-1')
        ->once()
        ->andReturn($streamResponse);

    $result = $builder->streamChat('conv-1');

    expect($result)->toBe($streamResponse);
});
