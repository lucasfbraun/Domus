<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OccurrenceUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $propertyName,
        public string $statusLabel,
        public ?string $resolutionNote = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ocorrencia atualizada: {$this->propertyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.occurrence-updated',
        );
    }
}
