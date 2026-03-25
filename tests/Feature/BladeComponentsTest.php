<?php

use Illuminate\Support\Facades\Blade;

it('renders the search component', function () {
    $html = Blade::render('<x-datalumo::search id="test-integration" />');

    expect($html)
        ->toContain('datalumo.js')
        ->toContain("Datalumo.searchBox('test-integration'")
        ->toContain('"baseUrl":"https:\/\/datalumo.test"');
});

it('renders the search component with optional attributes', function () {
    $html = Blade::render('<x-datalumo::search id="test-integration" target="#results" form="#my-form" input="#my-input" />');

    expect($html)
        ->toContain("Datalumo.searchBox('test-integration'")
        ->toContain('"target":"#results"')
        ->toContain('"form":"#my-form"')
        ->toContain('"input":"#my-input"');
});

it('renders the search-modal component', function () {
    $html = Blade::render('<x-datalumo::search-modal id="test-integration" />');

    expect($html)
        ->toContain('datalumo.js')
        ->toContain("Datalumo.searchModal('test-integration'");
});

it('renders the search-modal component with target', function () {
    $html = Blade::render('<x-datalumo::search-modal id="test-integration" target="#modal-container" />');

    expect($html)
        ->toContain("Datalumo.searchModal('test-integration'")
        ->toContain('"target":"#modal-container"');
});

it('renders the chatbot component', function () {
    $html = Blade::render('<x-datalumo::chatbot id="test-integration" />');

    expect($html)
        ->toContain('datalumo.js')
        ->toContain("Datalumo.chatbot('test-integration'");
});
