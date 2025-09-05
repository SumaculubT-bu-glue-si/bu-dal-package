<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorrectiveActionNotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $correctiveAction;
    public $employee;
    public $auditAsset;
    public $asset;
    public $auditPlan;
    public $priorityColor;
    public $statusColor;

    /**
     * Create a new message instance.
     */
    public function __construct($correctiveAction, $employee, $auditAsset, $asset, $auditPlan)
    {
        $this->correctiveAction = $correctiveAction;
        $this->employee = $employee;
        $this->auditAsset = $auditAsset;
        $this->asset = $asset;
        $this->auditPlan = $auditPlan;

        // Set priority and status colors for email styling
        $this->priorityColor = $this->getPriorityColor($correctiveAction->priority);
        $this->statusColor = $this->getStatusColor($correctiveAction->status);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 是正措置が必要 / Corrective Action Required: {$this->asset->asset_id} - {$this->correctiveAction->issue}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.corrective-action-notification',
            with: [
                'correctiveAction' => $this->correctiveAction,
                'employee' => $this->employee,
                'auditAsset' => $this->auditAsset,
                'asset' => $this->asset,
                'auditPlan' => $this->auditPlan,
                'priorityColor' => $this->priorityColor,
                'statusColor' => $this->statusColor,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get priority color for styling.
     */
    private function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'critical' => '#dc2626', // Red
            'high' => '#ea580c',     // Orange
            'medium' => '#ca8a04',   // Yellow
            'low' => '#059669',      // Green
            default => '#6b7280',    // Gray
        };
    }

    /**
     * Get status color for styling.
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'completed' => '#059669', // Green
            'in_progress' => '#ca8a04', // Yellow
            'overdue' => '#dc2626',   // Red
            'pending' => '#6b7280',   // Gray
            default => '#6b7280',     // Gray
        };
    }
}
