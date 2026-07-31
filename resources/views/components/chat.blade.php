@props([
    'widget' => \Datalumo\Laravel\Support\WidgetKey::embedKey(\Datalumo\Laravel\Support\WidgetKey::chat()),
    'baseUrl' => config('datalumo.base_url', 'https://datalumo.app'),
])

@php
    $key = $widget;
    $scriptBase = rtrim($baseUrl, '/');
@endphp

{{-- Bubble mounts to document.body; this node is only a mount hook for attributes. --}}
<div {{ $attributes->merge(['data-datalumo-chat' => $key]) }}></div>

@once('datalumo-widget-script')
    @push('datalumo-scripts')
        <script src="{{ $scriptBase }}/widget/v1/datalumo.js" defer></script>
    @endpush
@endonce

@push('datalumo-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Datalumo && typeof window.Datalumo.chat === 'function') {
                window.Datalumo.chat(@js($key)).mount();
            }
        });
    </script>
@endpush
