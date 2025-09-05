<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditPlanNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $auditPlan;
    public $employee;
    public $assignedAssets;
    public $dueDate;

    /**
     * Create a new message instance.
     */
    public function __construct($auditPlan, $employee, $assignedAssets, $dueDate)
    {
        $this->auditPlan = $auditPlan;
        $this->employee = $employee;
        $this->assignedAssets = $assignedAssets;
        $this->dueDate = $dueDate;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "新しい監査割り当て / New Audit Assignment: {$this->auditPlan->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.audit-plan-notification',
            with: [
                'auditPlan' => $this->auditPlan,
                'employee' => $this->employee,
                'assignedAssets' => $this->assignedAssets,
                'dueDate' => $this->dueDate,
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
