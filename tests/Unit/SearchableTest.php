<?php

use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Tests\Fixtures\SearchableModel;
use Datalumo\Laravel\Tests\Fixtures\UnsearchableModel;

it('returns the collection id from property', function () {
    $model = new SearchableModel;

    expect($model->searchableCollectionId())->toBe('col-test-123');
});

it('returns the integration id from property', function () {
    $model = new SearchableModel;

    expect($model->searchableIntegrationId())->toBe('int-test-456');
});

it('uses the table name as source type', function () {
    $model = new SearchableModel;

    expect($model->searchableSourceType())->toBe('articles');
});

it('returns the primary key as scout key', function () {
    $model = new SearchableModel(['id' => 42]);

    expect($model->getScoutKey())->toBe(42);
});

it('returns the key name as scout key name', function () {
    $model = new SearchableModel;

    expect($model->getScoutKeyName())->toBe('id');
});

it('generates searchable text', function () {
    $model = new SearchableModel(['title' => 'Hello', 'body' => 'World']);

    expect($model->toSearchableText())->toBe("Hello\n\nWorld");
});

it('generates searchable title', function () {
    $model = new SearchableModel(['title' => 'Hello']);

    expect($model->toSearchableTitle())->toBe('Hello');
});

it('generates searchable source url', function () {
    $model = new SearchableModel(['id' => 5]);

    expect($model->toSearchableSourceUrl())->toBe('https://example.com/articles/5');
});

it('returns null for default optional methods', function () {
    $model = new SearchableModel;

    expect($model->toSearchableMeta())->toBeNull();
});

it('returns null for default toSearchableSearchableMeta', function () {
    $model = new SearchableModel;

    expect($model->toSearchableSearchableMeta())->toBeNull();
});

it('reports shouldBeSearchable as true by default', function () {
    $model = new SearchableModel;

    expect($model->shouldBeSearchable())->toBeTrue();
});

it('can override shouldBeSearchable', function () {
    $model = new UnsearchableModel;

    expect($model->shouldBeSearchable())->toBeFalse();
});

