<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $auditPlan;
    public $employee;
    public $pendingAssets;
    public $daysRemaining;

    /**
     * Create a new message instance.
     */
    public function __construct($auditPlan, $employee, $pendingAssets, $daysRemaining)
    {
        $this->auditPlan = $auditPlan;
        $this->employee = $employee;
        $this->pendingAssets = $pendingAssets;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $urgency = $this->daysRemaining <= 1 ? '緊急 / URGENT: ' : '';
        $daysText = $this->daysRemaining <= 1 ? '残り' . $this->daysRemaining . '日 / ' . $this->daysRemaining . ' days remaining' : '残り' . $this->daysRemaining . '日 / ' . $this->daysRemaining . ' days remaining';
        return new Envelope(
            subject: "{$urgency}監査リマインダー / Audit Reminder: {$this->auditPlan->name} - {$daysText}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.audit-reminder',
            with: [
                'auditPlan' => $this->auditPlan,
                'employee' => $this->employee,
                'pendingAssets' => $this->pendingAssets,
                'daysRemaining' => $this->daysRemaining,
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
