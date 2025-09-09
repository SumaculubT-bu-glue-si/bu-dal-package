<?php

namespace Bu\Server\Services;

use Bu\Server\Models\Employee;
use Bu\Server\Models\AuditPlan;
use Bu\Server\Mail\AuditAccessEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AuditEmailService
{
    /**
     * Send the audit access email
     */
    public function sendAccessEmail(Employee $employee, AuditPlan $auditPlan, string $token, $expiresAt): bool
    {
        try {
            $frontendUrl = config('server.frontend_url', 'http://localhost:9002');
            $accessUrl = "{$frontendUrl}/employee-audits/access/{$token}";

            Mail::to($employee)->send(new AuditAccessEmail($accessUrl, $expiresAt, $employee->name));

            Log::info('Audit access email sent successfully', [
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'audit_plan_id' => $auditPlan->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send audit access email', [
                'error' => $e->getMessage(),
                'employee_id' => $employee->id,
                'audit_plan_id' => $auditPlan->id
            ]);

            return false;
        }
    }
}
