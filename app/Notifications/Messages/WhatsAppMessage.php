<?php

namespace App\Notifications\Messages;

/** Message payload a Notification returns from `toWhatsApp()` for WhatsAppChannel. */
class WhatsAppMessage
{
    public function __construct(
        public string $text,
    ) {}
}
