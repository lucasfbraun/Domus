<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractDocumentReviewedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $propertyName,
        public bool $approved,
        public ?string $reviewNote = null,
    ) {}

    public function envelope(): Envelope
    {
        $statusLabel = $this->approved ? 'aprovado' : 'rejeitado';

        return new Envelope(
            subject: "Contrato {$statusLabel}: {$this->propertyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contract-document-reviewed',
        );
    }
}
