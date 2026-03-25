@props(['id', 'target' => null, 'form' => null, 'input' => null])

<script src="{{ rtrim(config('datalumo.url', 'https://datalumo.app'), '/') }}/embed/datalumo.js"></script>
<script>
    Datalumo.searchBox('{{ $id }}', {!! json_encode(array_filter([
        'baseUrl' => rtrim(config('datalumo.url', 'https://datalumo.app'), '/'),
        'target' => $target,
        'form' => $form,
        'input' => $input,
    ])) !!});
</script>
