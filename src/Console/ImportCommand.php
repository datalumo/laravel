<?php

namespace Datalumo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class ImportCommand extends Command
{
    protected $signature = 'datalumo:import
        {model : Fully-qualified model class}
        {--chunk= : Chunk size (default config chunk_size, max 50)}';

    protected $description = 'Import all searchable models into Datalumo (batch upsert)';

    public function handle(): int
    {
        $class = $this->argument('model');

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            $this->error("[{$class}] is not a valid Eloquent model.");

            return self::FAILURE;
        }

        /** @var Model $model */
        $model = new $class;
        $chunk = min(50, max(1, (int) ($this->option('chunk') ?: config('datalumo.chunk_size', 50))));

        $query = $model->newQuery();
        $total = 0;

        $query->orderBy($model->getKeyName())->chunkById($chunk, function ($models) use (&$total): void {
            $searchable = $models->filter(fn (Model $m) => method_exists($m, 'shouldBeSearchable') ? $m->shouldBeSearchable() : true);

            if ($searchable->isEmpty()) {
                return;
            }

            $searchable->first()->queueMakeSearchable($searchable->values());
            $total += $searchable->count();
            $this->output->write('.');
        });

        $this->newLine();
        $this->info("Queued/accepted {$total} model(s) for indexing.");
        $this->comment('Accepted for indexing (202). Content is not immediately searchable.');

        return self::SUCCESS;
    }
}
