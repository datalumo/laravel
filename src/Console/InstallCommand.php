<?php

namespace Datalumo\Laravel\Console;

use Datalumo\Client;
use Datalumo\Exceptions\DatalumoException;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'datalumo:install
        {--force : Overwrite the published config file}';

    protected $description = 'Publish Datalumo config and verify API credentials via GET /me';

    public function handle(Client $client): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'datalumo-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $token = (string) config('datalumo.token');
        $org = (string) config('datalumo.organisation');

        if ($token === '' || $org === '') {
            $this->warn('Set DATALUMO_TOKEN and DATALUMO_ORG in your .env, then re-run datalumo:install to verify.');
            $this->line('Example:');
            $this->line('  DATALUMO_BASE_URL=https://datalumo.app');
            $this->line('  DATALUMO_ORG=your-org-public-id');
            $this->line('  DATALUMO_TOKEN=your-org-api-token');
            $this->line('  DATALUMO_SOURCE=docs');
            $this->line('  DATALUMO_SEARCH_WIDGET={org-public-id}/{search-widget-id}');
            $this->line('  DATALUMO_CHAT_WIDGET={org-public-id}/{chat-widget-id}');

            return self::SUCCESS;
        }

        try {
            $me = $client->me()->get();
        } catch (DatalumoException $e) {
            $this->error('Could not verify credentials: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Connected to '.$me->organisation->name.' ('.$me->organisation->id.')');
        $this->line('Token: '.$me->token->name);
        $this->line('Abilities: '.implode(', ', $me->token->abilities ?: ['(none)']));
        $this->line('Source scope: '.($me->token->sourceScope === null ? 'all sources' : implode(', ', $me->token->sourceScope)));

        $this->newLine();
        $this->line('Sources:');
        foreach ($me->sources as $source) {
            $this->line(sprintf('  - %s (%s)', $source->slug, $source->id));
        }

        $abilities = $me->token->abilities;
        foreach (['pages.write' => 'indexing', 'pages.read' => 'reconcile', 'search' => 'hydrate search'] as $ability => $feature) {
            if (! in_array($ability, $abilities, true)) {
                $this->warn("Token is missing ability [{$ability}] required for {$feature}.");
            }
        }

        $defaultSource = (string) config('datalumo.source');
        if ($defaultSource !== '' && $me->token->sourceScope !== null) {
            $allowed = collect($me->sources)->pluck('slug')->merge(collect($me->sources)->pluck('id'))->all();
            if (! in_array($defaultSource, $allowed, true)) {
                $this->warn("Configured source [{$defaultSource}] is outside this token's source_scope.");
            }
        }

        $this->newLine();
        $this->comment('For full Laravel DX, use a token with pages.read, pages.write, and search.');
        $this->comment('Add chat/summarize only if you call those endpoints server-side.');
        $this->comment('Token source_scope must cover every source attached to your search widget.');

        return self::SUCCESS;
    }
}
