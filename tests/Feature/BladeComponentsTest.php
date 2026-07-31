<?php

it('renders search with target option and mount without arguments', function () {
    $html = Blade::render(<<<'BLADE'
        <x-datalumo::search />
        @stack('datalumo-scripts')
    BLADE);

    expect($html)
        ->toContain('data-datalumo-search="org_test/w_search"')
        ->toContain('https://datalumo.app/widget/v1/datalumo.js')
        ->toContain('window.Datalumo.search(')
        ->toContain('{ target:')
        ->toContain(').mount();')
        ->not->toContain('.mount(\'[data-datalumo-search=')
        ->not->toContain('.mount("[data-datalumo-search=')
        ->not->toContain('.mount(\'[data-datalumo-search="');

    // @js() encodes slashes and quotes; assert the full call shape.
    expect($html)->toMatch(
        "/window\.Datalumo\.search\('org_test\\\\\/w_search',\s*\{\s*target:\s*'\[data-datalumo-search=\\\\u0022org_test\\\\\/w_search\\\\u0022\]'\s*\}\)\.mount\(\);/"
    );
});

it('renders chat with mount and no selector argument', function () {
    $html = Blade::render(<<<'BLADE'
        <x-datalumo::chat />
        @stack('datalumo-scripts')
    BLADE);

    expect($html)
        ->toContain('data-datalumo-chat="org_test/w_chat"')
        ->toContain('https://datalumo.app/widget/v1/datalumo.js')
        ->toContain("window.Datalumo.chat('org_test\/w_chat').mount();")
        ->not->toContain('.mount(\'[data-datalumo-chat=')
        ->not->toContain('.mount("[data-datalumo-chat=');
});

it('loads the widget script only once when both embeds are present', function () {
    $html = Blade::render(<<<'BLADE'
        <x-datalumo::search />
        <x-datalumo::chat />
        @stack('datalumo-scripts')
    BLADE);

    expect(substr_count($html, '/widget/v1/datalumo.js'))->toBe(1)
        ->and($html)->toContain('window.Datalumo.search(')
        ->and($html)->toContain('{ target:')
        ->and($html)->toContain("window.Datalumo.chat('org_test\/w_chat').mount();");
});

it('allows overriding the widget key', function () {
    $html = Blade::render(<<<'BLADE'
        <x-datalumo::search widget="org_other/w_other" />
        <x-datalumo::chat widget="org_other/w_chat_other" />
        @stack('datalumo-scripts')
    BLADE);

    expect($html)
        ->toContain("window.Datalumo.search('org_other\/w_other'")
        ->toContain("window.Datalumo.chat('org_other\/w_chat_other').mount();")
        ->toContain('{ target:');
});
