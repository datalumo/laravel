<?php

namespace Datalumo\Laravel\Console;

use Datalumo\Client;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ReconcileCommand extends Command
{
    protected $signature = 'datalumo:reconcile
        {model : Fully-qualified model class}
        {--force : Push all rows, ignore hash cache}
        {--prune : Delete remote pages whose external_id is not in the local key set (API-push-only sources)}
        {--dry-run : Report only; no writes}';

    protected $description = 'Reconcile local models with remote Datalumo pages';

    public function handle(Client $client): int
    {
        $class = $this->argument('model');

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            $this->error("[{$class}] is not a valid Eloquent model.");

            return self::FAILURE;
        }

        /** @var Model $model */
        $model = new $class;
        $source = $model->datalumoSource();
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $prune = (bool) $this->option('prune');

        $wouldUpsert = 0;
        $wouldDelete = 0;
        $localKeys = [];

        $model->newQuery()->orderBy($model->getKeyName())->chunkById(100, function ($models) use ($force, $dryRun, &$wouldUpsert, &$localKeys): void {
            $toPush = $models->filter(function (Model $m) use ($force, &$localKeys) {
                $key = (string) $m->getDatalumoKey();
                $localKeys[$key] = true;

                if (! $m->shouldBeSearchable()) {
                    return false;
                }

                if ($force) {
                    return true;
                }

                $cacheKey = 'datalumo:hash:'.get_class($m).':'.$key;
                $hash = $m->datalumoHash();
                $previous = Cache::get($cacheKey);

                return $previous !== $hash;
            });

            if ($toPush->isEmpty()) {
                return;
            }

            $wouldUpsert += $toPush->count();

            if (! $dryRun) {
                $toPush->first()->queueMakeSearchable($toPush->values());
                $toPush->each(function (Model $m): void {
                    Cache::forever(
                        'datalumo:hash:'.get_class($m).':'.$m->getDatalumoKey(),
                        $m->datalumoHash(),
                    );
                });
            }
        });

        if ($prune) {
            $cursor = null;

            do {
                $query = ['per_page' => 100];
                if ($cursor) {
                    $query['cursor'] = $cursor;
                }

                $list = $client->pages($source)->list($query);

                foreach ($list->data as $page) {
                    $externalId = $page->externalId;
                    if ($externalId === null || $externalId === '') {
                        continue;
                    }

                    if (! isset($localKeys[$externalId])) {
                        $wouldDelete++;
                        if (! $dryRun) {
                            $client->pages($source)->delete($externalId);
                        }
                    }
                }

                $cursor = $list->nextCursor;
            } while ($cursor);
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Would upsert / upserted: {$wouldUpsert}");
        if ($prune) {
            $this->info(($dryRun ? '[dry-run] ' : '')."Would delete / deleted orphans: {$wouldDelete}");
        } else {
            $this->comment('Orphan delete skipped (pass --prune for API-push-only sources).');
        }

        return self::SUCCESS;
    }
}
