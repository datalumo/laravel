<script src="{{ rtrim(config('datalumo.url', 'https://datalumo.com'), '/') }}/embed/datalumo.js"></script>
<script>
    Datalumo.searchBox('{{ $id }}', {
        baseUrl: '{{ rtrim(config('datalumo.url', 'https://datalumo.com'), '/') }}'@if(!empty($target)),
        target: '{{ $target }}'@endif@if(!empty($form)),
        form: '{{ $form }}'@endif@if(!empty($input)),
        input: '{{ $input }}'@endif
    });
</script>
