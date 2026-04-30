<?php

namespace Datalumo\Laravel\Console;

use Datalumo\PhpSdk\Datalumo;
use Datalumo\PhpSdk\DataObjects\Entry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

class ReconcileCommand extends Command
{
    protected $signature = 'datalumo:reconcile
        {model : The model class to reconcile}
        {--force : Push every row, ignoring the local hash cache}';

    protected $description = 'Sync the model to Datalumo and remove any stale entries that no longer exist';

    public function handle(Datalumo $datalumo): int
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

        $sample = new $class;
        $collectionId = $sample->searchableCollectionId();
        $sourceType = $sample->searchableSourceType();

        $this->info("Reconciling [{$class}]...");

        $cachePath = $this->cachePath($class);
        $cache = $this->loadCache($cachePath);
        $force = (bool) $this->option('force');

        $chunkSize = config('datalumo.chunk_size', 50);
        $upserted = 0;
        $skipped = 0;
        $kept = [];

        $class::query()->chunkById($chunkSize, function (EloquentCollection $models) use (&$upserted, &$skipped, &$kept, &$cache, $force) {
            $searchable = $models->filter->shouldBeSearchable();

            $searchable->each(function (Model $model) use (&$kept) {
                $kept[(string) $model->getScoutKey()] = true;
            });

            $changed = $force
                ? $searchable
                : $searchable->filter(function (Model $model) use ($cache) {
                    $key = (string) $model->getScoutKey();

                    return ($cache[$key] ?? null) !== $model->searchableHash();
                });

            $skipped += $searchable->count() - $changed->count();

            if ($changed->isNotEmpty()) {
                $changed->first()->searchableUsing()->update($changed);
                $upserted += $changed->count();

                $changed->each(function (Model $model) use (&$cache) {
                    $cache[(string) $model->getScoutKey()] = $model->searchableHash();
                });
            }

            $this->output->write('.');
        });

        $this->newLine();
        $this->info("Upserted [{$upserted}] records (skipped [{$skipped}] unchanged).");

        $this->info("Sweeping stale entries with source_type [{$sourceType}]...");

        $stale = [];
        $page = 1;

        do {
            $response = $datalumo->entries($collectionId)->list($page);

            foreach ($response->data as $entry) {
                /** @var Entry $entry */
                if ($entry->sourceType !== $sourceType) {
                    continue;
                }

                if (isset($kept[$entry->sourceId])) {
                    continue;
                }

                $stale[] = $entry->sourceId;
            }

            $page++;
        } while ($response->hasMorePages());

        foreach ($stale as $sourceId) {
            $datalumo->entries($collectionId)->deleteBySource($sourceType, $sourceId);
            unset($cache[$sourceId]);
            $this->output->write('x');
        }

        // Drop hashes for any source IDs no longer in the kept set (e.g. shouldBeSearchable() returned false).
        foreach (array_keys($cache) as $key) {
            if (! isset($kept[$key])) {
                unset($cache[$key]);
            }
        }

        $this->saveCache($cachePath, $cache);

        $this->newLine();
        $this->info('Removed ['.count($stale).'] stale entries.');

        return self::SUCCESS;
    }

    private function cachePath(string $class): string
    {
        $dir = storage_path('app/datalumo');

        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir.'/reconcile-'.md5($class).'.json';
    }

    /** @return array<string, string> */
    private function loadCache(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param  array<string, string>  $cache */
    private function saveCache(string $path, array $cache): void
    {
        file_put_contents($path, json_encode($cache, JSON_PRETTY_PRINT));
    }
}
