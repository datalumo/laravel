# Datalumo for Laravel

A Scout-inspired Laravel integration for [Datalumo](https://datalumo.com). Automatically sync your Eloquent models to Datalumo collections and search them with a fluent API.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

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
    'url' => env('DATALUMO_URL', 'https://datalumo.com'),
    'queue' => env('DATALUMO_QUEUE', true),
    'queue_connection' => env('DATALUMO_QUEUE_CONNECTION'),
    'queue_name' => env('DATALUMO_QUEUE_NAME'),
    'chunk_size' => 50,
];
```

By default, indexing operations are queued. Set `DATALUMO_QUEUE=false` to sync synchronously.

## Making Models Searchable

Add the `Searchable` trait to your Eloquent model and implement `toSearchableText()`:

```php
use Datalumo\Laravel\Searchable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Searchable;

    protected string $datalumoCollectionId = 'your-collection-uuid';

    public function toSearchableText(): string
    {
        return $this->title . "\n\n" . $this->body;
    }
}
```

The `$datalumoCollectionId` property must reference an existing collection in your Datalumo account. The `toSearchableText()` method defines the content that gets indexed and searched.

### Customising What Gets Indexed

Override these optional methods to enrich your entries:

```php
class Article extends Model
{
    use Searchable;

    protected string $datalumoCollectionId = 'your-collection-uuid';

    public function toSearchableText(): string
    {
        return $this->title . "\n\n" . $this->body;
    }

    public function toSearchableTitle(): ?string
    {
        return $this->title;
    }

    public function toSearchableMeta(): ?array
    {
        return [
            'author' => $this->author->name,
            'published_at' => $this->published_at->toIso8601String(),
        ];
    }

    public function toSearchableTags(): ?array
    {
        return $this->tags->pluck('name')->all();
    }

    public function toSearchableSourceUrl(): ?string
    {
        return route('articles.show', $this);
    }
}
```

### Conditional Indexing

Control which models get indexed by overriding `shouldBeSearchable()`:

```php
public function shouldBeSearchable(): bool
{
    return $this->is_published;
}
```

Models that return `false` are automatically removed from Datalumo if they were previously indexed.

### Custom Source Type and Key

By default, the table name is used as `source_type` and the primary key as the identifier. Override if needed:

```php
public function searchableSourceType(): string
{
    return 'blog_posts';
}

public function getScoutKey(): mixed
{
    return $this->uuid;
}

public function getScoutKeyName(): string
{
    return 'uuid';
}
```

## Automatic Syncing

Once a model uses the `Searchable` trait, it automatically syncs to Datalumo on:

- **Create** — indexed immediately (or queued)
- **Update** — re-indexed with new content
- **Delete** — removed from Datalumo
- **Soft Delete** — respects `shouldBeSearchable()` (removed if false)
- **Restore** — re-indexed

All sync operations use batch upsert under the hood via `source_type` and `source_id`.

### Manual Syncing

```php
// Index a single model
$article->searchable();

// Remove a single model
$article->unsearchable();

// Index a collection of models
Article::where('is_published', true)->get()->searchable();

// Remove a collection of models
Article::where('is_draft', true)->get()->unsearchable();
```

## Searching

### Basic Search

```php
$articles = Article::search('machine learning')->get();
```

This returns an Eloquent Collection of `Article` models, matched by semantic search in your Datalumo collection.

### Pagination

```php
$articles = Article::search('machine learning')->paginate(15);
```

Returns a standard Laravel `LengthAwarePaginator`, compatible with Blade and API responses.

### Similarity Threshold

Control how strict the matching is (0 = match everything, 1 = exact match):

```php
$articles = Article::search('machine learning')
    ->threshold(0.4)
    ->get();
```

### Filter by Tags

```php
$articles = Article::search('machine learning')
    ->tags(['ai', 'research'])
    ->get();

// Single tag
$articles = Article::search('machine learning')
    ->tags('ai')
    ->get();
```

### Chaining

All builder methods are fluent:

```php
$articles = Article::search('transformers')
    ->threshold(0.3)
    ->tags(['ai'])
    ->paginate(10);
```

### Raw Results

Get the raw Datalumo `Entry` objects without mapping to Eloquent models:

```php
$entries = Article::search('machine learning')->raw();

foreach ($entries as $entry) {
    echo $entry->title;
    echo $entry->rawText;
    echo $entry->sourceId;
}
```

## AI Features

### Summarise

Get an AI-generated summary of search results:

```php
$summary = Article::search('explain machine learning')->summarise();

echo $summary->summary;       // Markdown summary
echo $summary->references;    // Source references
echo $summary->data;          // Matched Entry objects

// With format and locale
$summary = Article::search('explain machine learning')
    ->summarise(format: 'html', locale: 'nl');
```

### Chat

Have a conversation grounded in your collection's content:

```php
$response = Article::search('What is machine learning?')->chat();

echo $response->message;          // AI response
echo $response->conversationId;   // Use to continue the conversation
```

Continue an existing conversation:

```php
$followUp = Article::search('What about deep learning?')
    ->chat($response->conversationId);
```

## Artisan Commands

### Import

Bulk import all existing models into Datalumo:

```bash
php artisan datalumo:import "App\Models\Article"
```

This processes models in chunks (default 50, configurable via `datalumo.chunk_size`) and respects `shouldBeSearchable()`.

### Flush

Remove all models of a type from Datalumo:

```bash
php artisan datalumo:flush "App\Models\Article"
```

This will ask for confirmation before proceeding.

## Queue Configuration

By default, all indexing operations are dispatched to the queue. Configure the connection and queue name via environment variables:

```env
DATALUMO_QUEUE=true
DATALUMO_QUEUE_CONNECTION=redis
DATALUMO_QUEUE_NAME=indexing
```

Set `DATALUMO_QUEUE=false` for synchronous indexing (useful during development).

## Testing

```bash
composer test
```

In your application tests, you can mock the `Engine` to prevent actual API calls:

```php
use Datalumo\Laravel\Engine;

$engine = Mockery::mock(Engine::class);
$engine->shouldReceive('update')->andReturnNull();
$engine->shouldReceive('delete')->andReturnNull();

$this->app->instance(Engine::class, $engine);
```
