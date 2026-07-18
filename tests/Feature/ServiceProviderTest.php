<?php

use Datalumo\Client;
use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Facades\Datalumo;

it('binds the client and engine', function () {
    expect(app(Client::class))->toBeInstanceOf(Client::class)
        ->and(app(Engine::class))->toBeInstanceOf(Engine::class)
        ->and(Datalumo::getFacadeRoot())->toBeInstanceOf(Client::class);
});

it('configures organisation on the client', function () {
    expect(app(Client::class)->organisation())->toBe('org_test');
});
