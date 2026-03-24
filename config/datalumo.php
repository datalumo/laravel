<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datalumo API Token
    |--------------------------------------------------------------------------
    |
    | Your Datalumo API token, used to authenticate all requests to the
    | Datalumo API. You can find this in your organisation settings.
    |
    */

    'token' => env('DATALUMO_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Datalumo API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Datalumo API. You should not need to change this
    | unless you are using a self-hosted instance.
    |
    */

    'url' => env('DATALUMO_URL', 'https://datalumo.com'),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Whether to queue indexing operations. When enabled, model changes are
    | synced to Datalumo via queued jobs instead of synchronously.
    |
    */

    'queue' => env('DATALUMO_QUEUE', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection & Queue Name
    |--------------------------------------------------------------------------
    |
    | Specify the queue connection and queue name for Datalumo jobs.
    |
    */

    'queue_connection' => env('DATALUMO_QUEUE_CONNECTION'),

    'queue_name' => env('DATALUMO_QUEUE_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | The number of records to process at a time when bulk importing
    | models via the datalumo:import command.
    |
    */

    'chunk_size' => 50,

];
