<?php

namespace Bu\DAL\Services;

use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\Employee;
use Bu\DAL\Models\Asset;
use Bu\DAL\Database\Repositories\LocationRepository;
use Bu\DAL\Database\Repositories\EmployeeRepository;
use Bu\DAL\Database\DatabaseManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AuditPlanNotificationEmail;
use App\Mail\AuditReminderEmail;

class AuditNotificationService
{
    /**
     * Send initial audit notifications to all employees with assets in audited locations
     */
    public function sendInitialNotifications(AuditPlan $auditPlan, array $auditorIds, array $locationIds)
    {
        try {
            Log::info('Starting to send initial audit notifications', [
                'audit_plan_id' => $auditPlan->id,
                'audit_plan_name' => $auditPlan->name,
                'auditor_ids' => $auditorIds,
                'location_ids' => $locationIds
            ]);

            // Get location names from IDs
            $locationNames = \App\Models\Location::whereIn('id', $locationIds)->pluck('name')->toArray();

            if (empty($locationNames)) {
                Log::warning('No locations found for notification', [
                    'audit_plan_id' => $auditPlan->id,
                    'location_ids' => $locationIds
                ]);
                return 0;
            }

            // Get ALL employees who have assets in the audited locations
            $allEmployees = Employee::whereHas('assignedAssets', function ($query) use ($locationNames) {
                $query->whereIn('location', $locationNames);
            })->get();

            // Also include assigned auditors (in case they don't have assets yet)
            $assignedAuditors = Employee::whereIn('id', $auditorIds)->get();

            // Merge and deduplicate employees
            $employees = $allEmployees->merge($assignedAuditors)->unique('id');

            if ($employees->isEmpty()) {
                Log::warning('No employees found for notification', [
                    'audit_plan_id' => $auditPlan->id,
                    'location_names' => $locationNames,
                    'auditor_ids' => $auditorIds
                ]);
                return 0;
            }

            Log::info('Found employees for notification', [
                'audit_plan_id' => $auditPlan->id,
                'total_employees' => $employees->count(),
                'employees_with_assets' => $allEmployees->count(),
                'assigned_auditors' => $assignedAuditors->count(),
                'location_names' => $locationNames
            ]);

            $sentCount = 0;
            $failedCount = 0;
            $results = [];

            foreach ($employees as $employee) {
                $result = $this->sendEmployeeNotification($auditPlan, $employee, $locationIds);

                if ($result) {
                    $sentCount++;
                    $results[] = [
                        'employee_id' => $employee->id,
                        'employee_email' => $employee->email,
                        'status' => 'success',
                        'has_assets' => $allEmployees->contains('id', $employee->id),
                        'is_auditor' => $assignedAuditors->contains('id', $employee->id)
                    ];
                } else {
                    $failedCount++;
                    $results[] = [
                        'employee_id' => $employee->id,
                        'employee_email' => $employee->email,
                        'status' => 'failed',
                        'has_assets' => $allEmployees->contains('id', $employee->id),
                        'is_auditor' => $assignedAuditors->contains('id', $employee->id)
                    ];
                }
            }

            Log::info('Initial audit notifications completed', [
                'audit_plan_id' => $auditPlan->id,
                'audit_plan_name' => $auditPlan->name,
                'total_employees' => $employees->count(),
                'notifications_sent' => $sentCount,
                'notifications_failed' => $failedCount,
                'location_names' => $locationNames,
                'results' => $results
            ]);

            return $sentCount;
        } catch (\Exception $e) {
            Log::error('Failed to send initial audit notifications', [
                'audit_plan_id' => $auditPlan->id,
                'audit_plan_name' => $auditPlan->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Send notification to a specific employee
     */
    private function sendEmployeeNotification(AuditPlan $auditPlan, Employee $employee, array $locationIds): bool
    {
        try {
            // Get assets assigned to this employee for this audit
            $assignedAssets = Asset::whereIn('location', function ($query) use ($locationIds) {
                $query->select('name')->from('locations')->whereIn('id', $locationIds);
            })
                ->where('user_id', $employee->id)
                ->get();

            // Send notification email
            Mail::to($employee->email)->send(new AuditPlanNotificationEmail(
                $auditPlan,
                $employee,
                $assignedAssets,
                $auditPlan->due_date
            ));

            Log::info('Audit notification sent successfully', [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'audit_plan_id' => $auditPlan->id,
                'assets_count' => $assignedAssets->count()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send audit notification to employee', [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'audit_plan_id' => $auditPlan->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send reminder emails for pending audits
     */
    public function sendReminders()
    {
        try {
            $activePlans = AuditPlan::where('status', 'In Progress')
                ->where('due_date', '>', now())
                ->get();

            $totalReminders = 0;

            foreach ($activePlans as $plan) {
                $remindersSent = $this->sendPlanReminders($plan);
                $totalReminders += $remindersSent;
            }

            Log::info('Audit reminders sent', [
                'total_plans_processed' => $activePlans->count(),
                'total_reminders_sent' => $totalReminders
            ]);

            return $totalReminders;
        } catch (\Exception $e) {
            Log::error('Failed to send audit reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Send reminders for a specific audit plan
     */
    private function sendPlanReminders(AuditPlan $plan): int
    {
        try {
            // Get all locations for this audit plan
            $locationNames = $plan->assignments()->with('location')->get()
                ->pluck('location.name')
                ->unique()
                ->toArray();

            if (empty($locationNames)) {
                Log::warning('No locations found for audit plan reminders', [
                    'audit_plan_id' => $plan->id
                ]);
                return 0;
            }

            // Get ALL employees who have assets in the audited locations
            $allEmployees = Employee::whereHas('assignedAssets', function ($query) use ($locationNames) {
                $query->whereIn('location', $locationNames);
            })->get();

            // Also include assigned auditors
            $assignedAuditors = $plan->assignments()
                ->where('status', '!=', 'Completed')
                ->with('auditor')
                ->get()
                ->pluck('auditor');

            // Merge and deduplicate employees
            $employees = $allEmployees->merge($assignedAuditors)->unique('id');

            if ($employees->isEmpty()) {
                Log::warning('No employees found for audit plan reminders', [
                    'audit_plan_id' => $plan->id,
                    'location_names' => $locationNames
                ]);
                return 0;
            }

            Log::info('Sending reminders for audit plan', [
                'audit_plan_id' => $plan->id,
                'total_employees' => $employees->count(),
                'employees_with_assets' => $allEmployees->count(),
                'assigned_auditors' => $assignedAuditors->count(),
                'location_names' => $locationNames
            ]);

            $remindersSent = 0;

            foreach ($employees as $employee) {
                // Get pending assets for this employee across all audited locations
                $pendingAssets = Asset::whereIn('location', $locationNames)
                    ->where('user_id', $employee->id)
                    ->whereHas('auditAssets', function ($query) use ($plan) {
                        $query->where('audit_plan_id', $plan->id)
                            ->where('audit_status', false);
                    })
                    ->get();

                if ($pendingAssets->count() > 0) {
                    $daysRemaining = now()->diffInDays($plan->due_date, false);

                    // Send reminder if due date is approaching (7, 3, and 1 day before)
                    if (in_array($daysRemaining, [7, 3, 1])) {
                        if ($this->sendReminderEmail($plan, $employee, $pendingAssets, $daysRemaining)) {
                            $remindersSent++;
                        }
                    }
                }
            }

            return $remindersSent;
        } catch (\Exception $e) {
            Log::error('Failed to send reminders for audit plan', [
                'audit_plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Send a reminder email to an employee
     */
    private function sendReminderEmail(AuditPlan $plan, Employee $employee, $pendingAssets, int $daysRemaining): bool
    {
        try {
            Mail::to($employee->email)->send(new AuditReminderEmail(
                $plan,
                $employee,
                $pendingAssets,
                $daysRemaining
            ));

            Log::info('Audit reminder sent', [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'audit_plan_id' => $plan->id,
                'days_remaining' => $daysRemaining,
                'pending_assets_count' => $pendingAssets->count()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send audit reminder email', [
                'employee_id' => $employee->id,
                'audit_plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
