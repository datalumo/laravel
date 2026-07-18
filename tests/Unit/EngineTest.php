<?php

use Datalumo\Client;
use Datalumo\Data\SearchHit;
use Datalumo\Data\SearchResult;
use Datalumo\Laravel\Builder;
use Datalumo\Laravel\Engine;
use Datalumo\Laravel\Tests\Fixtures\Article;
use Datalumo\Resources\PagesResource;
use Datalumo\Resources\WidgetsResource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery as m;

beforeEach(function () {
    Schema::create('articles', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->text('body')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('articles');
    m::close();
});

it('batch upserts pages through the sdk', function () {
    $pages = m::mock(PagesResource::class);
    $pages->shouldReceive('batchUpsert')
        ->once()
        ->with(m::on(function (array $payload) {
            return count($payload) === 1
                && $payload[0]['external_id'] === '1'
                && $payload[0]['content'] === 'Body';
        }))
        ->andReturn([]);

    $client = m::mock(Client::class);
    $client->shouldReceive('pages')->with('docs')->andReturn($pages);

    $engine = new Engine($client);

    $article = Article::withoutEvents(fn () => Article::query()->create([
        'title' => 'Hello',
        'body' => 'Body',
    ]));

    $engine->update($article->newCollection([$article]));
});

it('maps search hits by external_id identity and drops nulls', function () {
    Article::withoutEvents(function () {
        Article::query()->create(['title' => 'One', 'body' => 'a']);
        Article::query()->create(['title' => 'Two', 'body' => 'b']);
    });

    $widgets = m::mock(WidgetsResource::class);
    $widgets->shouldReceive('search')->once()->andReturn(new SearchResult(
        data: [
            new SearchHit('p1', '2', 'Two', null, null, 0.9, null),
            new SearchHit('p2', null, 'Orphan', null, null, 0.5, null),
            new SearchHit('p3', '1', 'One', null, null, 0.4, null),
        ],
        sessionId: 'sess',
    ));

    $client = m::mock(Client::class);
    $client->shouldReceive('widgets')->with('w_test')->andReturn($widgets);

    $this->app->instance(Client::class, $client);
    $this->app->instance(Engine::class, new Engine($client));

    $results = Article::datalumoSearch('q')->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('title')->all())->toBe(['Two', 'One']);
});

it('builds search params with filters', function () {
    $widgets = m::mock(WidgetsResource::class);
    $widgets->shouldReceive('search')
        ->once()
        ->with(m::on(fn (array $params) => $params['query'] === 'deploy'
            && ($params['filters']['author'] ?? null) === 'Jane'
            && ($params['limit'] ?? null) === 10))
        ->andReturn(new SearchResult(data: [], sessionId: 's'));

    $client = m::mock(Client::class);
    $client->shouldReceive('widgets')->andReturn($widgets);

    $engine = new Engine($client);
    $builder = new Builder(new Article, 'deploy');
    $builder->where('author', 'Jane')->limit(10);

    $engine->search($builder);
});
