<script src="{{ rtrim(config('datalumo.url', 'https://datalumo.app'), '/') }}/embed/datalumo.js"></script>
<script>
    Datalumo.searchBox('{{ $id }}', {
        baseUrl: '{{ rtrim(config('datalumo.url', 'https://datalumo.app'), '/') }}'@if(!empty($target)),
        target: '{{ $target }}'@endif@if(!empty($form)),
        form: '{{ $form }}'@endif@if(!empty($input)),
        input: '{{ $input }}'@endif
    });
</script>
