<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditAccessEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $accessUrl;
    public $expiresAt;
    public $employeeName;

    /**
     * Create a new message instance.
     */
    public function __construct($accessUrl, $expiresAt, $employeeName = null)
    {
        $this->accessUrl = $accessUrl;
        $this->expiresAt = $expiresAt;
        $this->employeeName = $employeeName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'あなたの監査アクセスリンク / Your Audit Access Link',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.audit-access',
            with: [
                'accessUrl' => $this->accessUrl,
                'expiresAt' => $this->expiresAt,
                'employeeName' => $this->employeeName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
