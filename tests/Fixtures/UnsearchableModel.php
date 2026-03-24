<?php

namespace Datalumo\Laravel\Tests\Fixtures;

use Datalumo\Laravel\Searchable;
use Illuminate\Database\Eloquent\Model;

class UnsearchableModel extends Model
{
    use Searchable;

    protected $table = 'articles';

    protected $guarded = [];

    public string $datalumoCollectionId = 'col-test-123';

    public string $datalumoIntegrationId = 'int-test-456';

    public function toSearchableText(): string
    {
        return $this->title ?? '';
    }

    public function shouldBeSearchable(): bool
    {
        return false;
    }
}
