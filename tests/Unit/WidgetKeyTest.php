<?php

use Datalumo\Laravel\Support\WidgetKey;

it('resolves search and chat widgets separately', function () {
    config([
        'datalumo.search_widget' => 'org/search',
        'datalumo.chat_widget' => 'org/chat',
        'datalumo.widget' => 'org/fallback',
    ]);

    expect(WidgetKey::search())->toBe('org/search')
        ->and(WidgetKey::chat())->toBe('org/chat')
        ->and(WidgetKey::publicId(WidgetKey::search()))->toBe('search')
        ->and(WidgetKey::publicId(WidgetKey::chat()))->toBe('chat');
});

it('falls back to widget when search or chat is empty', function () {
    config([
        'datalumo.search_widget' => null,
        'datalumo.chat_widget' => '',
        'datalumo.widget' => 'org/shared',
    ]);

    expect(WidgetKey::search())->toBe('org/shared')
        ->and(WidgetKey::chat())->toBe('org/shared');
});

it('builds an embed key from organisation when only a public id is given', function () {
    config(['datalumo.organisation' => 'org_abc']);

    expect(WidgetKey::embedKey('w_1'))->toBe('org_abc/w_1')
        ->and(WidgetKey::embedKey('org_abc/w_1'))->toBe('org_abc/w_1');
});
