@props([
    'widget' => \Datalumo\Laravel\Support\WidgetKey::embedKey(\Datalumo\Laravel\Support\WidgetKey::search()),
    'session' => null,
    'baseUrl' => config('datalumo.base_url', 'https://datalumo.app'),
])

@php
    $scriptBase = rtrim($baseUrl, '/');
@endphp

@once
    <script
        src="{{ $scriptBase }}/widget/v1/datalumo-analytics.js"
        data-widget="{{ $widget }}"
        @if ($session) data-session="{{ $session }}" @endif
        defer
    ></script>
@endonce
