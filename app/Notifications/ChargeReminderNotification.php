<?php

namespace App\Notifications;

use App\Models\Charge;
use App\Notifications\Concerns\DeterminesNotificationChannels;
use App\Notifications\Messages\WhatsAppMessage;
use App\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChargeReminderNotification extends Notification implements ShouldQueue
{
    use DeterminesNotificationChannels;
    use Queueable;

    /**
     * @param  array{event: string, tenantName: string, amount: float, dueDate: string, paymentUrl: string, propertyName: string, daysLate?: int|null}  $context
     */
    public function __construct(
        public Charge $charge,
        public array $context,
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $propertyName = $this->context['propertyName'];
        $amount = 'R$ '.number_format($this->context['amount'], 2, ',', '.');
        $subject = match ($this->context['event']) {
            'after_due' => "Aluguel em atraso: {$propertyName}",
            'due_day' => "Aluguel vence hoje: {$propertyName}",
            default => "Lembrete de aluguel: {$propertyName}",
        };

        $line = match ($this->context['event']) {
            'after_due' => "Identificamos aluguel em atraso no valor atualizado de {$amount}.",
            'due_day' => "Seu aluguel vence hoje ({$this->context['dueDate']}), no valor de {$amount}.",
            default => "Lembrete do aluguel com vencimento em {$this->context['dueDate']}, valor {$amount}.",
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Ola, {$this->context['tenantName']}!")
            ->line($line)
            ->line("Imovel: {$propertyName}")
            ->action('Acessar portal', $this->context['paymentUrl']);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return new WhatsAppMessage(
            app(WhatsAppClient::class)->buildReminderText($this->context),
        );
    }
}
