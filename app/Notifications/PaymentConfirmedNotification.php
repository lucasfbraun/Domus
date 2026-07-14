<?php

namespace App\Notifications;

use App\Models\Charge;
use App\Notifications\Concerns\DeterminesNotificationChannels;
use App\Notifications\Messages\WhatsAppMessage;
use App\Services\MercadoPagoService;
use App\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use DeterminesNotificationChannels;
    use Queueable;

    public function __construct(public Charge $charge) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $propertyName = $this->propertyName();

        return (new MailMessage)
            ->subject("Pagamento confirmado: {$propertyName}")
            ->markdown('mail.payment-confirmed', [
                'propertyName' => $propertyName,
                'amount' => $this->formattedAmount(),
            ]);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $this->charge->loadMissing(['contract.tenant', 'contract.property']);

        return new WhatsAppMessage(
            app(WhatsAppClient::class)->buildReminderText([
                'event' => 'payment_confirmed',
                'tenantName' => $this->charge->contract?->tenant?->name ?? $notifiable->name ?? '',
                'amount' => $this->amountDue(),
                'dueDate' => $this->charge->due_date->timezone('America/Sao_Paulo')->format('d/m/Y'),
                'paymentUrl' => rtrim((string) config('services.app_base_url'), '/').'/portal/tenant',
                'propertyName' => $this->propertyName(),
            ]),
        );
    }

    private function propertyName(): string
    {
        $this->charge->loadMissing('contract.property');

        return $this->charge->contract?->property?->name ?? 'imovel';
    }

    private function amountDue(): float
    {
        return app(MercadoPagoService::class)->computeCurrentAmountDue($this->charge);
    }

    private function formattedAmount(): string
    {
        return 'R$ '.number_format($this->amountDue(), 2, ',', '.');
    }
}
