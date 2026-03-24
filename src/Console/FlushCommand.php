<?php

namespace Datalumo\Laravel\Console;

use Illuminate\Console\Command;

class FlushCommand extends Command
{
    protected $signature = 'datalumo:flush {model : The model class to flush}';

    protected $description = 'Remove all models of a given type from Datalumo';

    public function handle(): int
    {
        $class = $this->argument('model');

        if (! class_exists($class)) {
            $this->error("Class [{$class}] not found.");

            return self::FAILURE;
        }

        if (! method_exists($class, 'searchableUsing')) {
            $this->error("Class [{$class}] does not use the Searchable trait.");

            return self::FAILURE;
        }

        if (! $this->confirm("This will remove all [{$class}] records from Datalumo. Continue?")) {
            return self::SUCCESS;
        }

        $this->info("Flushing [{$class}]...");

        $chunkSize = config('datalumo.chunk_size', 50);
        $count = 0;

        $class::query()->chunkById($chunkSize, function ($models) use (&$count) {
            $models->first()->searchableUsing()->delete($models);
            $count += $models->count();

            $this->output->write('.');
        });

        $this->newLine();
        $this->info("Removed [{$count}] records.");

        return self::SUCCESS;
    }
}
