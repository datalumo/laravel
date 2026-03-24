<?php

namespace Datalumo\Laravel\Tests\Fixtures;

use Datalumo\Laravel\Searchable;
use Illuminate\Database\Eloquent\Model;

class SearchableModel extends Model
{
    use Searchable;

    protected $table = 'articles';

    protected $guarded = [];

    public string $datalumoCollectionId = 'col-test-123';

    public string $datalumoIntegrationId = 'int-test-456';

    public function toSearchableText(): string
    {
        return $this->title."\n\n".$this->body;
    }

    public function toSearchableTitle(): ?string
    {
        return $this->title;
    }

    public function toSearchableTags(): ?array
    {
        return $this->tags ?? null;
    }

    public function toSearchableSourceUrl(): ?string
    {
        return 'https://example.com/articles/'.$this->id;
    }
}
