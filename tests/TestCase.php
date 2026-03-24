<?php

namespace Datalumo\Laravel\Tests;

use Datalumo\Laravel\DatalumoServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DatalumoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('datalumo.token', 'test-token');
        $app['config']->set('datalumo.url', 'https://datalumo.test');
        $app['config']->set('datalumo.queue', false);
    }
}
