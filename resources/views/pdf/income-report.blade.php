<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Informe de Rendimentos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .subtitle { color: #555; margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        .label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #ddd; }
        th { background: #f3f4f6; }
        td.amount, th.amount { text-align: right; }
        tfoot td { font-weight: bold; border-top: 2px solid #999; border-bottom: none; }
    </style>
</head>
<body>
    <h1>INFORME DE RENDIMENTOS</h1>
    <div class="subtitle">
        Período: {{ $months[0]['label'] ?? $year }}{{ count($months) > 1 ? ' a '.end($months)['label'] : '' }}
    </div>

    <div class="section">
        @if ($owner)
            <div><span class="label">Proprietário:</span> {{ $owner->name }} — {{ \App\Support\BrazilianDocument::format($owner->document) }}</div>
        @endif
        @if ($receiver)
            <div><span class="label">Recebedor:</span> {{ $receiver->name }} — {{ \App\Support\BrazilianDocument::format($receiver->document) }}</div>
        @endif
        <div><span class="label">Valor total recebido no período:</span> {{ \App\Support\Money::format($total) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mês</th>
                <th class="amount">Rendimento líquido</th>
                <th class="amount">Nº de pagamentos</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($months as $monthRow)
                <tr>
                    <td>{{ $monthRow['label'] }}</td>
                    <td class="amount">{{ \App\Support\Money::format($monthRow['total']) }}</td>
                    <td class="amount">{{ $monthRow['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="amount">{{ \App\Support\Money::format($total) }}</td>
                <td class="amount">{{ count($payments) }}</td>
            </tr>
        </tfoot>
    </table>

    @if (count($payments) > 0)
        <div class="section label">Detalhamento dos pagamentos</div>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Referência</th>
                    <th>Imóvel</th>
                    <th>Inquilino</th>
                    <th>Recebedor</th>
                    <th class="amount">Valor líquido</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($payment['paid_at'])->format('d/m/Y') }}</td>
                        <td>{{ $payment['reference'] }}</td>
                        <td>{{ $payment['property'] }}</td>
                        <td>{{ $payment['tenant'] }}</td>
                        <td>{{ $payment['receiver'] }}</td>
                        <td class="amount">{{ \App\Support\Money::format($payment['net_amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div>Emitido em {{ now()->timezone('America/Sao_Paulo')->format('d/m/Y') }}.</div>
</body>
</html>
