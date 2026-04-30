<?php

namespace Datalumo\Laravel;

use Datalumo\Laravel\Console\FlushCommand;
use Datalumo\Laravel\Console\ImportCommand;
use Datalumo\Laravel\Console\ReconcileCommand;
use Datalumo\PhpSdk\Datalumo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class DatalumoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/datalumo.php', 'datalumo');

        $this->app->singleton(Datalumo::class, function () {
            return new Datalumo(
                token: config('datalumo.token'),
                baseUrl: config('datalumo.url'),
            );
        });

        $this->app->singleton(Engine::class, function ($app) {
            return new Engine($app->make(Datalumo::class));
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'datalumo');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'datalumo');

        $this->publishes([
            __DIR__.'/../config/datalumo.php' => config_path('datalumo.php'),
        ], 'datalumo-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/datalumo'),
        ], 'datalumo-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCommand::class,
                FlushCommand::class,
                ReconcileCommand::class,
            ]);
        }
    }
}
