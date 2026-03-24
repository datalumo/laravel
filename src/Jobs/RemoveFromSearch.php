<?php

namespace Datalumo\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveFromSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Collection $models) {}

    public function handle(): void
    {
        if ($this->models->isEmpty()) {
            return;
        }

        $this->models->first()->searchableUsing()->delete($this->models);
    }
}
