<?php

namespace Bu\Server\Services;

use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\AuditAsset;
use Bu\Server\Models\Employee;
use Bu\Server\Models\Asset;
use Bu\Server\Models\CorrectiveAction;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\Location;
use Bu\Server\Mail\AuditPlanNotificationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuditAccessService
{
    public function handleAccessRequest($email, $auditPlanId)
    {
        try {
            Log::info('Processing access request', [
                'email' => $email,
                'audit_plan_id' => $auditPlanId
            ]);

            // Check if audit plan exists
            $auditPlan = AuditPlan::findOrFail($auditPlanId);

            // Find employee by email
            $employee = Employee::where('email', $email)->first();

            if (!$employee) {
                Log::warning('Employee not found', ['email' => $email]);
                return [
                    'success' => false,
                    'message' => 'Employee not found with the provided email.'
                ];
            }

            // Get assigned assets for the employee
            $assignedAssets = AuditAsset::where('audit_plan_id', $auditPlanId)
                ->whereHas('asset', function ($query) use ($employee) {
                    $query->where('user', $employee->email);
                })
                ->with('asset')
                ->get();

            if ($assignedAssets->isEmpty()) {
                Log::warning('No assets for employee in this audit', [
                    'email' => $email,
                    'audit_plan_id' => $auditPlanId
                ]);
            }

            // Send notification email with due date
            Mail::to($email)->send(new AuditPlanNotificationEmail(
                $auditPlan,
                $employee,
                $assignedAssets,
                $auditPlan->due_date
            ));

            Log::info('Access request processed successfully', [
                'email' => $email,
                'audit_plan_id' => $auditPlanId
            ]);

            return [
                'success' => true,
                'message' => 'Access request processed successfully.'
            ];
        } catch (\Exception $e) {
            Log::error('Error processing access request', [
                'email' => $email,
                'audit_plan_id' => $auditPlanId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process access request: ' . $e->getMessage()
            ];
        }
    }
}
