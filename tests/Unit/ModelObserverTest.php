<?php

use Datalumo\Laravel\ModelObserver;
use Datalumo\Laravel\Tests\Fixtures\SearchableModel;
use Datalumo\Laravel\Tests\Fixtures\UnsearchableModel;

beforeEach(function () {
    $this->observer = new ModelObserver;
});

it('makes model searchable on save when shouldBeSearchable is true', function () {
    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('shouldBeSearchable')->andReturn(true);
    $model->shouldReceive('searchable')->once();

    $this->observer->saved($model);
});

it('removes model from search on save when shouldBeSearchable is false and model changed', function () {
    $model = Mockery::mock(UnsearchableModel::class)->makePartial();
    $model->shouldReceive('shouldBeSearchable')->andReturn(false);
    $model->shouldReceive('wasChanged')->andReturn(true);
    $model->shouldReceive('unsearchable')->once();

    $this->observer->saved($model);
});

it('does nothing on save when shouldBeSearchable is false and model not changed', function () {
    $model = Mockery::mock(UnsearchableModel::class)->makePartial();
    $model->shouldReceive('shouldBeSearchable')->andReturn(false);
    $model->shouldReceive('wasChanged')->andReturn(false);
    $model->shouldNotReceive('unsearchable');
    $model->shouldNotReceive('searchable');

    $this->observer->saved($model);
});

it('removes model from search on hard delete', function () {
    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('unsearchable')->once();

    $this->observer->deleted($model);
});

it('removes model from search on force delete', function () {
    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('unsearchable')->once();

    $this->observer->forceDeleted($model);
});

it('syncs model on restore', function () {
    $model = Mockery::mock(SearchableModel::class)->makePartial();
    $model->shouldReceive('shouldBeSearchable')->andReturn(true);
    $model->shouldReceive('searchable')->once();

    $this->observer->restored($model);
});
