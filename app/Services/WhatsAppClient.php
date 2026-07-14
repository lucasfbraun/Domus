<?php

namespace App\Services;

use App\Support\Money;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppClient
{
    /**
     * @param  array{tenantPhone: string, text: string}  $message
     */
    public function sendText(array $message): void
    {
        $url = config('services.waha.url');
        $apiKey = config('services.waha.api_key');
        $session = config('services.waha.session', 'default');

        if (! $url || ! $apiKey) {
            Log::warning('[whatsapp] WAHA not configured; message not sent.');

            return;
        }

        $chatId = $this->normalizeBrazilianPhoneToChatId($message['tenantPhone']);

        $response = Http::withHeaders([
            'X-Api-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post(rtrim($url, '/').'/api/sendText', [
            'session' => $session,
            'chatId' => $chatId,
            'text' => $message['text'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Falha ao enviar WhatsApp via WAHA (status '.$response->status().'): '.$response->body(),
            );
        }
    }

    public function normalizeBrazilianPhoneToChatId(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return $digits.'@c.us';
    }

    /**
     * @param  array{event: string, tenantName: string, amount: float, dueDate: string, paymentUrl: string, propertyName?: string, daysLate?: int}  $reminder
     */
    public function buildReminderText(array $reminder): string
    {
        $formattedAmount = Money::format($reminder['amount']);

        return match ($reminder['event']) {
            'payment_confirmed' => "Ola, {$reminder['tenantName']}. Seu pagamento de {$formattedAmount} foi confirmado. Obrigado!",
            'after_due' => "Ola, {$reminder['tenantName']}. Identificamos aluguel em atraso no valor atualizado de {$formattedAmount}. Acesse o link para pagamento: {$reminder['paymentUrl']}",
            'contract_expiring' => $this->buildContractExpiringText($reminder),
            default => "Ola, {$reminder['tenantName']}. Lembrete do aluguel com vencimento em {$reminder['dueDate']}, valor {$formattedAmount}. Link para pagamento: {$reminder['paymentUrl']}",
        };
    }

    /**
     * @param  array{tenantName: string, dueDate: string, propertyName?: string}  $reminder
     */
    private function buildContractExpiringText(array $reminder): string
    {
        $propertyText = isset($reminder['propertyName']) ? " do imovel {$reminder['propertyName']}" : '';

        return "Ola, {$reminder['tenantName']}. Seu contrato de locacao{$propertyText} vence em {$reminder['dueDate']}. Entre em contato para tratar da renovacao.";
    }
}
