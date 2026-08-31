<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Notifications\Concerns\DeterminesNotificationChannels;
use App\Notifications\Messages\WhatsAppMessage;
use App\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification implements ShouldQueue
{
    use DeterminesNotificationChannels;
    use Queueable;

    public function __construct(public Contract $contract) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->contract->loadMissing(['tenant', 'property']);
        $propertyName = $this->contract->property->name ?? 'imovel';
        $dueDate = $this->contract->ends_at->timezone('America/Sao_Paulo')->format('d/m/Y');

        return (new MailMessage)
            ->subject("Contrato vencendo: {$propertyName}")
            ->greeting("Ola, {$this->contract->tenant?->name}!")
            ->line("Seu contrato de locacao do imovel {$propertyName} vence em {$dueDate}.")
            ->line('Entre em contato para tratar da renovacao.');
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $this->contract->loadMissing(['tenant', 'property']);

        return new WhatsAppMessage(
            app(WhatsAppClient::class)->buildReminderText([
                'event' => 'contract_expiring',
                'tenantName' => $this->contract->tenant->name ?? '',
                'amount' => 0,
                'dueDate' => $this->contract->ends_at->timezone('America/Sao_Paulo')->format('d/m/Y'),
                'paymentUrl' => rtrim((string) config('services.app_base_url'), '/').'/portal/tenant',
                'propertyName' => $this->contract->property?->name,
            ]),
        );
    }
}
