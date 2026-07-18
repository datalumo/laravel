<?php

namespace Datalumo\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class FlushCommand extends Command
{
    protected $signature = 'datalumo:flush
        {model : Fully-qualified model class}
        {--from= : Resume after this primary key (exclusive)}
        {--chunk=100 : Local DB chunk size}';

    protected $description = 'Remove all of a model\'s pages from Datalumo (sequential deletes)';

    public function handle(): int
    {
        $class = $this->argument('model');

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            $this->error("[{$class}] is not a valid Eloquent model.");

            return self::FAILURE;
        }

        /** @var Model $model */
        $model = new $class;
        $keyName = $model->getKeyName();
        $chunk = max(1, (int) $this->option('chunk'));
        $from = $this->option('from');

        $query = $model->newQuery()->orderBy($keyName);

        if ($from !== null && $from !== '') {
            $query->where($keyName, '>', $from);
        }

        $count = (clone $query)->count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunkById($chunk, function ($models) use ($bar): void {
            $models->each(function (Model $m) use ($bar): void {
                $m->unsearchable();
                $bar->advance();
            });
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Flush complete.');
        $this->comment('Deletes run one by one under the ingest rate limit. Large tables take a while.');

        return self::SUCCESS;
    }
}
