<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OccurrenceReportedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantName,
        public string $propertyName,
        public string $description,
        public int $photoCount = 0,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nova ocorrencia reportada: {$this->propertyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.occurrence-reported',
        );
    }
}
