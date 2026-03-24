<?php

namespace Datalumo\Laravel\Console;

use Illuminate\Console\Command;

class ImportCommand extends Command
{
    protected $signature = 'datalumo:import {model : The model class to import}';

    protected $description = 'Import all models of a given type into Datalumo';

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

        $this->info("Importing [{$class}]...");

        $chunkSize = config('datalumo.chunk_size', 50);
        $count = 0;

        $class::query()->chunkById($chunkSize, function ($models) use (&$count) {
            $searchable = $models->filter->shouldBeSearchable();

            if ($searchable->isNotEmpty()) {
                $searchable->first()->searchableUsing()->update($searchable);
                $count += $searchable->count();
            }

            $this->output->write('.');
        });

        $this->newLine();
        $this->info("Imported [{$count}] records.");

        return self::SUCCESS;
    }
}
