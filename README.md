# datalumo for Laravel

A Scout-inspired Laravel integration for [datalumo](https://datalumo.app). Automatically sync your Eloquent models to Datalumo collections and search them via integrations with a fluent API.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require datalumo/laravel
```

Publish the config file:

```bash
php artisan vendor:publish --tag=datalumo-config
```

Add your API token to `.env`:

```env
DATALUMO_TOKEN=your-api-token
```

## Configuration

The published config file (`config/datalumo.php`) includes:

```php
return [
    'token' => env('DATALUMO_TOKEN', ''),
    'url' => env('DATALUMO_URL', 'https://datalumo.app'),
    'queue' => env('DATALUMO_QUEUE', true),
    'queue_connection' => env('DATALUMO_QUEUE_CONNECTION'),
    'queue_name' => env('DATALUMO_QUEUE_NAME'),
    'chunk_size' => 50,
];
```

By default, indexing operations are queued. Set `DATALUMO_QUEUE=false` to sync synchronously.

## Making models searchable

Add the `Searchable` trait to your Eloquent model and implement `toSearchableText()`:

```php
use Datalumo\Laravel\Searchable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Searchable;

    protected string $datalumoCollectionId = 'your-collection-uuid';
    protected string $datalumoIntegrationId = 'your-integration-uuid';

    public function toSearchableText(): string
    {
        return $this->title . "\n\n" . $this->body;
    }
}
```

The model requires two properties:

- `$datalumoCollectionId` — the collection where entries are synced to (data management)
- `$datalumoIntegrationId` — the integration used for search, summarise, and chat

### Customising what gets indexed

Override these optional methods to enrich your entries:

```php
public function toSearchableTitle(): ?string
{
    return $this->title;
}

public function toSearchableMeta(): ?array
{
    return ['author' => $this->author->name];
}

public function toSearchableSourceUrl(): ?string
{
    return route('articles.show', $this);
}
```

### Conditional indexing

Control which models get indexed:

```php
public function shouldBeSearchable(): bool
{
    return $this->is_published;
}
```

Models that return `false` are automatically removed from Datalumo if they were previously indexed.

## Automatic syncing

Once a model uses the `Searchable` trait, it automatically syncs to Datalumo on create, update, delete, soft delete, and restore.

### Manual syncing

```php
$article->searchable();
$article->unsearchable();

Article::where('is_published', true)->get()->searchable();
```

## Searching

### Basic search

```php
$articles = Article::search('machine learning')->get();
```

Returns an Eloquent Collection of matching models, searched via the integration.

### Pagination

```php
$articles = Article::search('machine learning')->paginate(15);
```

### Fluent options

```php
$articles = Article::search('transformers')
    ->threshold(0.3)
    ->meta(['category' => 'ai'])
    ->paginate(10);
```

### Raw results

Get the raw Datalumo `Entry` objects without mapping to Eloquent models:

```php
$entries = Article::search('machine learning')->raw();
```

## AI features

### Summarise

```php
$summary = Article::search('explain machine learning')->summarise();

echo $summary->summary;
echo $summary->references;
```

### Chat

```php
$response = Article::search('What is machine learning?')->chat();

echo $response->message;
echo $response->conversationId;

// Continue the conversation
$followUp = Article::search('What about deep learning?')
    ->chat($response->conversationId);
```

## Streaming

The summarise and chat methods have streaming variants that return text chunks as they are generated:

```php
$stream = Article::search('What is your refund policy?')->streamChat();

foreach ($stream->text() as $chunk) {
    echo $chunk;
    flush();
}
```

```php
$stream = Article::search('explain this')->streamSummarise();

foreach ($stream->text() as $chunk) {
    echo $chunk;
    flush();
}
```

Get the full text at once:

```php
$stream = Article::search('hello')->streamChat();
$fullResponse = $stream->fullText();
```

Use with Laravel's streaming response:

```php
Route::get('/chat', function () {
    $stream = Article::search(request('message'))->streamChat();

    return response()->stream(function () use ($stream) {
        foreach ($stream->text() as $chunk) {
            echo $chunk;
            ob_flush();
            flush();
        }
    }, 200, ['Content-Type' => 'text/plain']);
});
```

## Blade components

Embed Datalumo widgets directly in your views:

### Chatbot

```blade
<x-datalumo::chatbot id="your-integration-id" />
```

### Search box

```blade
<x-datalumo::search id="your-integration-id" />
```

With a custom target and form:

```blade
<x-datalumo::search
    id="your-integration-id"
    form="#my-form"
    input="#my-input"
    target="#results"
/>
```

### Search modal

Opens with `Ctrl+K` / `Cmd+K`:

```blade
<x-datalumo::search-modal id="your-integration-id" />
```

Publish views to customise:

```bash
php artisan vendor:publish --tag=datalumo-views
```

## Artisan commands

### Import

```bash
php artisan datalumo:import "App\Models\Article"
```

### Flush

```bash
php artisan datalumo:flush "App\Models\Article"
```

## Queue configuration

```env
DATALUMO_QUEUE=true
DATALUMO_QUEUE_CONNECTION=redis
DATALUMO_QUEUE_NAME=indexing
```

Set `DATALUMO_QUEUE=false` for synchronous indexing during development.

## Testing

In your tests, mock the `Engine` to prevent API calls:

```php
use Datalumo\Laravel\Engine;

$engine = Mockery::mock(Engine::class);
$engine->shouldReceive('update')->andReturnNull();
$engine->shouldReceive('delete')->andReturnNull();

$this->app->instance(Engine::class, $engine);
```
