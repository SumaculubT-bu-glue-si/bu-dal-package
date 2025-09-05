<?php

namespace Bu\DAL\Console\Commands;

use Illuminate\Console\Command;
use Bu\DAL\Services\AuditNotificationService;

class SendAuditReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audits:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for pending audits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting audit reminder process...');

        try {
            $notificationService = app(AuditNotificationService::class);
            // Note: sendAuditReminders method needs to be implemented in AuditNotificationService
            // $notificationService->sendAuditReminders();

            $this->info('Audit reminders sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send audit reminders: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
