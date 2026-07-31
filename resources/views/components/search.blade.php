@props([
    'widget' => \Datalumo\Laravel\Support\WidgetKey::embedKey(\Datalumo\Laravel\Support\WidgetKey::search()),
    'baseUrl' => config('datalumo.base_url', 'https://datalumo.app'),
])

@php
    $key = $widget;
    $scriptBase = rtrim($baseUrl, '/');
    $targetSelector = '[data-datalumo-search="'.$key.'"]';
@endphp

<div {{ $attributes->merge(['data-datalumo-search' => $key]) }}></div>

@once('datalumo-widget-script')
    @push('datalumo-scripts')
        <script src="{{ $scriptBase }}/widget/v1/datalumo.js" defer></script>
    @endpush
@endonce

@push('datalumo-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Datalumo && typeof window.Datalumo.search === 'function') {
                window.Datalumo.search(@js($key), { target: @js($targetSelector) }).mount();
            }
        });
    </script>
@endpush
