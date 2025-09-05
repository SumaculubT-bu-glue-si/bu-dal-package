<?php

namespace Bu\DAL\Console\Commands;

use Illuminate\Console\Command;
use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\Employee;
use Bu\DAL\Models\Location;
use Bu\DAL\Models\Asset;
use Bu\DAL\Services\AuditNotificationService;
use Bu\DAL\Services\CorrectiveActionNotificationService;

class TestAuditSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audits:test-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the audit system functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing audit system...');

        try {
            // Test data counts
            $this->info('Checking data counts:');
            $this->line("Audit Plans: " . AuditPlan::count());
            $this->line("Employees: " . Employee::count());
            $this->line("Locations: " . Location::count());
            $this->line("Assets: " . Asset::count());

            // Test services
            $this->info('Testing services...');

            // Test AuditNotificationService
            try {
                $auditService = app(AuditNotificationService::class);
                $this->line("✓ AuditNotificationService loaded successfully");
            } catch (\Exception $e) {
                $this->error("✗ AuditNotificationService failed: " . $e->getMessage());
            }

            // Test CorrectiveActionNotificationService
            try {
                $correctiveService = app(CorrectiveActionNotificationService::class);
                $this->line("✓ CorrectiveActionNotificationService loaded successfully");
            } catch (\Exception $e) {
                $this->error("✗ CorrectiveActionNotificationService failed: " . $e->getMessage());
            }

            // Test database connections
            $this->info('Testing database connection...');
            try {
                \Illuminate\Support\Facades\DB::connection()->getPdo();
                $this->line("✓ Database connection successful");
            } catch (\Exception $e) {
                $this->error("✗ Database connection failed: " . $e->getMessage());
            }

            $this->info('Audit system test completed successfully!');
        } catch (\Exception $e) {
            $this->error('Audit system test failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
