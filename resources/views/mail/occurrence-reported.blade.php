<x-mail::message>
# Nova ocorrencia reportada

O inquilino **{{ $tenantName }}** registrou uma ocorrencia no imovel **{{ $propertyName }}**.

{{ $description }}

@if ($photoCount > 0)
Acesse o painel de ocorrencias para revisar ({{ $photoCount }} foto(s) anexada(s)).
@else
Acesse o painel de ocorrencias para revisar.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
