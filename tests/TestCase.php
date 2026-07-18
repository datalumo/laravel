<?php

namespace Datalumo\Laravel\Tests;

use Datalumo\Laravel\DatalumoServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DatalumoServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('datalumo.token', 'test-token');
        $app['config']->set('datalumo.organisation', 'org_test');
        $app['config']->set('datalumo.source', 'docs');
        $app['config']->set('datalumo.search_widget', 'org_test/w_search');
        $app['config']->set('datalumo.chat_widget', 'org_test/w_chat');
        $app['config']->set('datalumo.queue', false);
        $app['config']->set('datalumo.base_url', 'https://datalumo.app');
    }
}
