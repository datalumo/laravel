@props([
    'widget' => config('datalumo.widget'),
    'baseUrl' => config('datalumo.base_url', 'https://datalumo.app'),
])

@php
    $key = $widget;
    $scriptBase = rtrim($baseUrl, '/');
@endphp

<div {{ $attributes->merge(['data-datalumo-chat' => $key]) }}></div>

@once
    @push('datalumo-scripts')
        <script src="{{ $scriptBase }}/widget/v1/datalumo.js" defer></script>
    @endpush
@endonce

@push('datalumo-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Datalumo && typeof window.Datalumo.chat === 'function') {
                window.Datalumo.chat(@js($key)).mount('[data-datalumo-chat="@js($key)"]');
            }
        });
    </script>
@endpush
