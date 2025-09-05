<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConsolidatedCorrectiveActionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $correctiveActions;
    public $employee;
    public $auditPlan;
    public $totalActions;

    /**
     * Create a new message instance.
     */
    public function __construct(\Illuminate\Support\Collection $correctiveActions, $employee, $auditPlan)
    {
        $this->correctiveActions = $correctiveActions;
        $this->employee = $employee;
        $this->auditPlan = $auditPlan;
        $this->totalActions = $correctiveActions->count();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📋 是正措置サマリー / Corrective Actions Summary: {$this->totalActions}件のアクションが必要 / action(s) require your attention",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.consolidated-corrective-action',
            with: [
                'correctiveActions' => $this->correctiveActions,
                'employee' => $this->employee,
                'auditPlan' => $this->auditPlan,
                'totalActions' => $this->totalActions,
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
}
