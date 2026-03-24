<?php

use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Jobs\RemoveFromSearch;
use Datalumo\Laravel\Tests\Fixtures\SearchableModel;

it('calls engine delete with the models', function () {
    $engine = Mockery::mock(Engine::class);
    $engine->shouldReceive('delete')->once();

    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('searchableUsing')->andReturn($engine);

    $models = new \Illuminate\Database\Eloquent\Collection([$model]);

    $job = new RemoveFromSearch($models);
    $job->handle();
});

it('does nothing for empty collection', function () {
    $job = new RemoveFromSearch(new \Illuminate\Database\Eloquent\Collection);
    $job->handle();

    expect(true)->toBeTrue();
});
