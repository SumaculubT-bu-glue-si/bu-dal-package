<?php

namespace Bu\DAL\Console\Commands;

use Illuminate\Console\Command;
use Bu\DAL\Services\CorrectiveActionNotificationService;

class SendCorrectiveActionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'corrective-actions:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for overdue corrective actions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting corrective action reminder process...');

        try {
            $notificationService = app(CorrectiveActionNotificationService::class);
            $notificationService->sendCorrectiveActionReminders();

            $this->info('Corrective action reminders sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send corrective action reminders: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
