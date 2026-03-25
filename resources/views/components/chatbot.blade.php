@props(['id'])

<script src="{{ rtrim(config('datalumo.url', 'https://datalumo.app'), '/') }}/embed/datalumo.js"></script>
<script>
    Datalumo.chatbot('{{ $id }}', {
        baseUrl: '{{ rtrim(config('datalumo.url', 'https://datalumo.app'), '/') }}'
    });
</script>
