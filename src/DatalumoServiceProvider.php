<?php

namespace Datalumo\Laravel;

use Datalumo\Client;
use Datalumo\HttpClientOptions;
use Datalumo\Laravel\Console\FlushCommand;
use Datalumo\Laravel\Console\ImportCommand;
use Datalumo\Laravel\Console\InstallCommand;
use Datalumo\Laravel\Console\ReconcileCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DatalumoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('datalumo')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                InstallCommand::class,
                ImportCommand::class,
                FlushCommand::class,
                ReconcileCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Client::class, function (): Client {
            return new Client(
                token: (string) config('datalumo.token', ''),
                organisation: (string) config('datalumo.organisation', ''),
                baseUrl: (string) config('datalumo.base_url', 'https://datalumo.app'),
                options: new HttpClientOptions,
            );
        });

        $this->app->singleton(Engine::class, function ($app): Engine {
            return new Engine($app->make(Client::class));
        });
    }
}
