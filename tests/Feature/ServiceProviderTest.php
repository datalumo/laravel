<?php

use Datalumo\Laravel\Engine;
use Datalumo\PhpSdk\Datalumo;

it('registers the Datalumo SDK as a singleton', function () {
    $instance1 = app(Datalumo::class);
    $instance2 = app(Datalumo::class);

    expect($instance1)->toBeInstanceOf(Datalumo::class)
        ->and($instance1)->toBe($instance2);
});

it('registers the Engine as a singleton', function () {
    $instance1 = app(Engine::class);
    $instance2 = app(Engine::class);

    expect($instance1)->toBeInstanceOf(Engine::class)
        ->and($instance1)->toBe($instance2);
});

it('merges config from package', function () {
    expect(config('datalumo.token'))->toBe('test-token')
        ->and(config('datalumo.url'))->toBe('https://datalumo.test')
        ->and(config('datalumo.queue'))->toBeFalse()
        ->and(config('datalumo.chunk_size'))->toBe(50);
});

it('resolves engine via searchableUsing on a model', function () {
    $model = new \Datalumo\Laravel\Tests\Fixtures\SearchableModel;

    expect($model->searchableUsing())->toBeInstanceOf(Engine::class);
});
