# datalumo/laravel

Laravel package for [Datalumo](https://datalumo.app). Sync Eloquent models, search them, and embed widgets.

Built on [`datalumo/php`](https://github.com/datalumo/php).

## Install

```bash
composer require datalumo/laravel
php artisan vendor:publish --tag=datalumo-config
```

Requires PHP 8.2+ and Laravel 11 or 12.

## Configure

```env
DATALUMO_BASE_URL=https://datalumo.app
DATALUMO_ORG=your-org-public-id
DATALUMO_TOKEN=your-api-token
DATALUMO_SOURCE=docs
DATALUMO_WIDGET={org-public-id}/{widget-public-id}
```

`DATALUMO_ORG` is the organisation public id, not the slug.

Optional check:

```bash
php artisan datalumo:install
```

## Make a model searchable

```php
use Datalumo\Laravel\Searchable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Searchable;

    public function toDatalumoArray(): array
    {
        return [
            'external_id' => (string) $this->getKey(),
            'name' => $this->title,
            'content' => $this->body,
            'content_mime' => 'text/markdown',
            'source_url' => route('articles.show', $this),
        ];
    }
}
```

Models sync on save and delete (queued by default).

Import existing rows:

```bash
php artisan datalumo:import "App\Models\Article"
```

## Search

```php
$articles = Article::datalumoSearch('how to deploy')->get();
```

## Embeds

```blade
<x-datalumo::search />
<x-datalumo::chat />

@stack('datalumo-scripts')
```

## Next steps

- [API docs](https://datalumo.app/docs)
- [PHP SDK](https://github.com/datalumo/php)
