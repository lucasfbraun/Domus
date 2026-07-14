<x-mail::message>
# Ocorrencia atualizada

Sua ocorrencia sobre o imovel **{{ $propertyName }}** foi atualizada para **{{ $statusLabel }}**.

@if ($resolutionNote)
Observacao: {{ $resolutionNote }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
