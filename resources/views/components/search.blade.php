@props([
    'widget' => config('datalumo.widget'),
    'baseUrl' => config('datalumo.base_url', 'https://datalumo.app'),
])

@php
    $key = $widget;
    $scriptBase = rtrim($baseUrl, '/');
@endphp

<div {{ $attributes->merge(['data-datalumo-search' => $key]) }}></div>

@once
    @push('datalumo-scripts')
        <script src="{{ $scriptBase }}/widget/v1/datalumo.js" defer></script>
    @endpush
@endonce

@push('datalumo-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Datalumo && typeof window.Datalumo.search === 'function') {
                window.Datalumo.search(@js($key)).mount('[data-datalumo-search="@js($key)"]');
            }
        });
    </script>
@endpush
