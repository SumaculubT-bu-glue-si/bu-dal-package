<?php

namespace Bu\DAL\Console\Commands;

use Illuminate\Console\Command;
use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\Employee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TestAuditPlanAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audits:test-access {audit_plan_id} {employee_email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test audit plan access for an employee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $auditPlanId = $this->argument('audit_plan_id');
        $employeeEmail = $this->argument('employee_email');

        $this->info("Testing audit plan access for employee: {$employeeEmail}");

        try {
            // Find audit plan
            $auditPlan = AuditPlan::findOrFail($auditPlanId);
            $this->info("Found audit plan: {$auditPlan->name}");

            // Find employee
            $employee = Employee::where('email', $employeeEmail)->first();
            if (!$employee) {
                $this->error("Employee with email {$employeeEmail} not found");
                return 1;
            }

            $this->info("Found employee: {$employee->name}");

            // Generate access token
            $accessToken = Str::random(32);
            $expiresAt = now()->addHours(24);

            // Cache the access token
            Cache::put("audit_access_{$accessToken}", [
                'audit_plan_id' => $auditPlanId,
                'employee_id' => $employee->id,
                'expires_at' => $expiresAt,
            ], $expiresAt);

            $this->info("Access token generated: {$accessToken}");
            $this->info("Token expires at: {$expiresAt}");

            // Test access URL
            $accessUrl = url("/api/employee-audits/access/{$accessToken}");
            $this->info("Access URL: {$accessUrl}");
        } catch (\Exception $e) {
            $this->error('Failed to test audit plan access: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
