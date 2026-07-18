<?php

return [

    // organisation is the public id (UUID), not the slug.
    // Widget keys are {org-public-id}/{widget-public-id}.

    'base_url' => env('DATALUMO_BASE_URL', 'https://datalumo.app'),

    'organisation' => env('DATALUMO_ORG'),

    'token' => env('DATALUMO_TOKEN'),

    'source' => env('DATALUMO_SOURCE'),

    // Embeds and server-side search/chat use separate widgets (different types).
    'search_widget' => env('DATALUMO_SEARCH_WIDGET'),
    'chat_widget' => env('DATALUMO_CHAT_WIDGET'),

    // Optional fallback when search_widget / chat_widget are not set.
    'widget' => env('DATALUMO_WIDGET'),

    'widget_signing_secret' => env('DATALUMO_WIDGET_SIGNING_SECRET'),

    'queue' => env('DATALUMO_QUEUE', true),

    'queue_connection' => env('DATALUMO_QUEUE_CONNECTION'),

    'queue_name' => env('DATALUMO_QUEUE_NAME'),

    'chunk_size' => (int) env('DATALUMO_CHUNK_SIZE', 50),

    'search' => [
        'limit' => (int) env('DATALUMO_SEARCH_LIMIT', 15),
    ],

    // Optional: \App\Models\Article::class => 'docs'
    'model_sources' => [
        //
    ],

];
