<script src="{{ rtrim(config('datalumo.url', 'https://datalumo.com'), '/') }}/embed/datalumo.js"></script>
<script>
    Datalumo.searchModal('{{ $id }}', {
        baseUrl: '{{ rtrim(config('datalumo.url', 'https://datalumo.com'), '/') }}'
    });
</script>
