<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Recibo de Pagamento</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.6; color: #111; }
        h1 { font-size: 18px; margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <h1>RECIBO DE PAGAMENTO DE ALUGUEL</h1>

    <div class="section">
        <div><span class="label">Referencia:</span> {{ $charge->reference }}</div>
        @if ($charge->rateio_amount > 0)
            <div><span class="label">Aluguel:</span> {{ \App\Support\Money::format((float) $charge->original_amount - (float) $charge->rateio_amount) }}</div>
            <div><span class="label">Rateio:</span> {{ \App\Support\Money::format((float) $charge->rateio_amount) }}</div>
            <div><span class="label">Valor pago (total):</span> {{ \App\Support\Money::format((float) ($payment?->amount_paid ?? $charge->original_amount)) }}</div>
        @else
            <div><span class="label">Valor pago:</span> {{ \App\Support\Money::format((float) ($payment?->amount_paid ?? $charge->original_amount)) }}</div>
        @endif
        <div><span class="label">Vencimento:</span> {{ $charge->due_date->format('d/m/Y') }}</div>
    </div>

    <div class="section">
        <div class="label">LOCATARIO (Inquilino)</div>
        <div>{{ $contract->tenant->name }}</div>
        <div>{{ \App\Support\BrazilianDocument::format($contract->tenant->document) }}</div>
    </div>

    <div class="section">
        <div class="label">LOCADOR (Recebedor)</div>
        <div>{{ $contract->receiver->name }}</div>
        <div>{{ \App\Support\BrazilianDocument::format($contract->receiver->document) }}</div>
    </div>

    <div class="section">
        <div class="label">IMOVEL</div>
        <div>{{ $contract->property->name }}</div>
        <div>{{ $contract->property->address }}</div>
    </div>

    <div class="section">
        Declaro para os devidos fins que recebi a quantia acima discriminada e dou total quitacao da referida obrigacao.
    </div>

    <div>{{ now()->timezone('America/Sao_Paulo')->format('d/m/Y') }}</div>
</body>
</html>
