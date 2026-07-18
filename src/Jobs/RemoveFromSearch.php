<?php

namespace Datalumo\Laravel\Jobs;

use Datalumo\Exceptions\RateLimitException;
use Datalumo\Laravel\Engine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveFromSearch implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public EloquentCollection $models) {}

    public function handle(Engine $engine): void
    {
        try {
            $engine->delete($this->models);
        } catch (RateLimitException $e) {
            $this->release($e->retryAfterSeconds() ?? 5);
        }
    }
}
