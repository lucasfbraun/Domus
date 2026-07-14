<x-mail::message>
# Contrato {{ $approved ? 'aprovado' : 'rejeitado' }}

O contrato do imovel **{{ $propertyName }}** foi **{{ $approved ? 'aprovado' : 'rejeitado' }}**.

@if ($reviewNote)
Observacao do administrador: {{ $reviewNote }}
@endif

@unless ($approved)
O inquilino pode enviar um novo arquivo assinado pelo portal.
@endunless

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
