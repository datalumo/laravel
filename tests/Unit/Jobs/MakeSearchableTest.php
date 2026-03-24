<?php

use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Jobs\MakeSearchable;
use Datalumo\Laravel\Tests\Fixtures\SearchableModel;

it('calls engine update with the models', function () {
    $engine = Mockery::mock(Engine::class);
    $engine->shouldReceive('update')->once()
        ->withArgs(fn ($collection) => $collection->count() === 1);

    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('searchableUsing')->andReturn($engine);

    $models = new \Illuminate\Database\Eloquent\Collection([$model]);

    $job = new MakeSearchable($models);
    $job->handle();
});

it('does nothing for empty collection', function () {
    $job = new MakeSearchable(new \Illuminate\Database\Eloquent\Collection);
    $job->handle();

    expect(true)->toBeTrue();
});
