@props(['id', 'target' => null])

<script src="{{ rtrim(config('datalumo.url', 'https://datalumo.app'), '/') }}/embed/datalumo.js"></script>
<script>
    Datalumo.searchModal('{{ $id }}', {!! json_encode(array_filter([
        'baseUrl' => rtrim(config('datalumo.url', 'https://datalumo.app'), '/'),
        'target' => $target,
    ])) !!});
</script>
