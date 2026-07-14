<x-mail::message>
# Pagamento confirmado

@if (! empty($amount))
O pagamento referente ao imovel **{{ $propertyName }}** foi confirmado no valor de **{{ $amount }}**.
@else
O pagamento referente ao imovel **{{ $propertyName }}** foi confirmado.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
