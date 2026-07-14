<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\WhatsAppChannel;

trait DeterminesNotificationChannels
{
    /**
     * @return list<string|class-string>
     */
    protected function channelsFor(object $notifiable): array
    {
        $channels = [];

        if (filled($notifiable->routeNotificationFor('mail', $this))) {
            $channels[] = 'mail';
        }

        if (filled($notifiable->routeNotificationFor('whatsapp', $this))) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }
}
