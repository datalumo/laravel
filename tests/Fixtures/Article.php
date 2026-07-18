<?php

namespace Datalumo\Laravel\Tests\Fixtures;

use Datalumo\Laravel\Searchable;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use Searchable;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'articles';

    public function toDatalumoArray(): array
    {
        return [
            'external_id' => (string) $this->getKey(),
            'name' => $this->title,
            'content' => $this->body ?? '',
            'content_mime' => 'text/plain',
        ];
    }
}
