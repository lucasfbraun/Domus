<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\WhatsAppMessage;
use App\Services\WhatsAppClient;
use Illuminate\Notifications\Notification;

/**
 * Custom Laravel notification channel: a Notification opts in by defining
 * `toWhatsApp($notifiable): WhatsAppMessage` and the notifiable routes via
 * `routeNotificationFor('whatsapp', ...)`. Missing method, empty message, or
 * no route silently no-ops instead of failing the notification dispatch.
 */
class WhatsAppChannel
{
    public function __construct(
        private WhatsAppClient $whatsAppClient,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (! $message instanceof WhatsAppMessage || $message->text === '') {
            return;
        }

        $phone = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! filled($phone)) {
            return;
        }

        $this->whatsAppClient->sendText([
            'tenantPhone' => $phone,
            'text' => $message->text,
        ]);
    }
}
