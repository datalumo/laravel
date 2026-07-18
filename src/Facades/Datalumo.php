<?php

namespace Datalumo\Laravel\Facades;

use Datalumo\Client;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Datalumo\Resources\MeResource me()
 * @method static \Datalumo\Resources\PagesResource pages(string $source)
 * @method static \Datalumo\Resources\WidgetsResource widgets(string $widget)
 * @method static Client http()
 *
 * @see Client
 */
class Datalumo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
