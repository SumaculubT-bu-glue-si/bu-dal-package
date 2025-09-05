<?php

namespace Bu\DAL\Services;

use Bu\DAL\Models\CorrectiveAction;
use Bu\DAL\Models\CorrectiveActionAssignment;
use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Models\Asset;
use Bu\DAL\Models\Employee;
use App\Mail\CorrectiveActionNotificationEmail;
use App\Mail\ConsolidatedCorrectiveActionEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CorrectiveActionNotificationService
{
    /**
     * Send notifications for a newly created corrective action
     * Now uses consolidated emails to avoid multiple emails to the same employee
     */
    public function sendCorrectiveActionNotification(CorrectiveAction $correctiveAction): array
    {
        try {
            Log::info('Starting to send consolidated corrective action notifications', [
                'corrective_action_id' => $correctiveAction->id,
                'audit_asset_id' => $correctiveAction->audit_asset_id,
                'audit_plan_id' => $correctiveAction->audit_plan_id
            ]);

            // Get the audit asset and related data
            $auditAsset = $correctiveAction->auditAsset;
            if (!$auditAsset) {
                Log::warning('Audit asset not found for corrective action', [
                    'corrective_action_id' => $correctiveAction->id,
                    'audit_asset_id' => $correctiveAction->audit_asset_id
                ]);
                return ['success' => false, 'message' => 'Audit asset not found'];
            }

            $asset = $auditAsset->asset;
            if (!$asset) {
                Log::warning('Asset not found for audit asset', [
                    'corrective_action_id' => $correctiveAction->id,
                    'audit_asset_id' => $correctiveAction->audit_asset_id,
                    'asset_id' => $auditAsset->asset_id
                ]);
                return ['success' => false, 'message' => 'Asset not found'];
            }

            $auditPlan = $correctiveAction->auditPlan;
            if (!$auditPlan) {
                Log::warning('Audit plan not found for corrective action', [
                    'corrective_action_id' => $correctiveAction->id,
                    'audit_plan_id' => $correctiveAction->audit_plan_id
                ]);
                return ['success' => false, 'message' => 'Audit plan not found'];
            }

            // Find employees to notify - prioritize assigned_to field over asset ownership
            $employeesToNotify = collect();

            // First check if there's an assigned employee in the assigned_to field
            if ($correctiveAction->assigned_to) {
                $assignedEmployee = Employee::find($correctiveAction->assigned_to);
                if ($assignedEmployee) {
                    $employeesToNotify->push($assignedEmployee);
                    Log::info('Using assigned employee from assigned_to field', [
                        'employee_id' => $assignedEmployee->id,
                        'employee_name' => $assignedEmployee->name,
                        'corrective_action_id' => $correctiveAction->id
                    ]);
                }
            }

            // Fallback to asset owner if no assignment found
            if ($employeesToNotify->isEmpty()) {
                $employeesToNotify = $this->findEmployeesToNotify($correctiveAction, $asset, $auditAsset);
                Log::info('Falling back to asset owner for notifications', [
                    'corrective_action_id' => $correctiveAction->id,
                    'asset_id' => $asset->id
                ]);
            }

            if ($employeesToNotify->isEmpty()) {
                Log::warning('No employees found to notify for corrective action', [
                    'corrective_action_id' => $correctiveAction->id,
                    'asset_id' => $asset->id,
                    'assigned_to' => $correctiveAction->assigned_to ?? 'null'
                ]);
                return ['success' => false, 'message' => 'No employees found to notify'];
            }

            Log::info('Found employees to notify for corrective action', [
                'corrective_action_id' => $correctiveAction->id,
                'employees_count' => count($employeesToNotify),
                'employee_ids' => $employeesToNotify->pluck('id')->toArray()
            ]);

            $sentCount = 0;
            $failedCount = 0;
            $results = [];

            // For single actions, we still send individual emails but this ensures
            // that if multiple actions are created for the same employee, they'll get
            // consolidated emails when using the bulk reminder system
            foreach ($employeesToNotify as $employee) {
                $result = $this->sendEmployeeNotification($correctiveAction, $employee, $auditAsset, $asset, $auditPlan);

                if ($result) {
                    $sentCount++;
                    $results[] = [
                        'employee_id' => $employee->id,
                        'employee_email' => $employee->email,
                        'status' => 'success'
                    ];
                } else {
                    $failedCount++;
                    $results[] = [
                        'employee_id' => $employee->id,
                        'employee_email' => $employee->email,
                        'status' => 'failed'
                    ];
                }
            }

            Log::info('Corrective action notifications completed', [
                'corrective_action_id' => $correctiveAction->id,
                'total_employees' => count($employeesToNotify),
                'notifications_sent' => $sentCount,
                'notifications_failed' => $failedCount,
                'results' => $results
            ]);

            return [
                'success' => true,
                'message' => "Notifications sent: {$sentCount}, Failed: {$failedCount}",
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_employees' => count($employeesToNotify),
                'results' => $results
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send consolidated corrective action notifications', [
                'corrective_action_id' => $correctiveAction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Find employees who should be notified about the corrective action
     */
    private function findEmployeesToNotify(CorrectiveAction $correctiveAction, Asset $asset, AuditAsset $auditAsset): \Illuminate\Support\Collection
    {
        $employees = collect();

        // ONLY notify the employee currently assigned to the asset (user_id) - PRIMARY RECIPIENT
        if ($asset->user_id) {
            $assignedEmployee = Employee::find($asset->user_id);
            if ($assignedEmployee) {
                $employees->push($assignedEmployee);
                Log::info('Adding ONLY the primary assigned employee to notifications', [
                    'employee_id' => $assignedEmployee->id,
                    'employee_name' => $assignedEmployee->name,
                    'asset_id' => $asset->id
                ]);
            }
        }

        // REMOVED: All other notification logic
        // - No corrective action assignments (auditors can't resolve issues)
        // - No original user assignments (not relevant for resolution)
        // - No location-based assignments (auditors can't monitor)
        // Only notify the asset owner who can actually fix the issue

        Log::info('Final employee notification list (asset owner only)', [
            'corrective_action_id' => $correctiveAction->id,
            'asset_id' => $asset->id,
            'total_employees' => $employees->count(),
            'employee_ids' => $employees->pluck('id')->toArray()
        ]);

        return $employees->unique('id');
    }

    /**
     * Send notification to a specific employee
     */
    private function sendEmployeeNotification(CorrectiveAction $correctiveAction, Employee $employee, AuditAsset $auditAsset, Asset $asset, $auditPlan): bool
    {
        try {
            Mail::to($employee->email)->send(new CorrectiveActionNotificationEmail(
                $correctiveAction,
                $employee,
                $auditAsset,
                $asset,
                $auditPlan
            ));

            Log::info('Corrective action notification sent successfully', [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'corrective_action_id' => $correctiveAction->id,
                'asset_id' => $asset->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send corrective action notification to employee', [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'corrective_action_id' => $correctiveAction->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send consolidated reminder notifications for overdue corrective actions
     * Groups actions by employee to send one email per person with all their overdue actions
     */
    public function sendOverdueReminders(): array
    {
        try {
            $overdueActions = CorrectiveAction::where('due_date', '<', now())
                ->where('status', '!=', 'completed')
                ->with(['auditAsset.asset', 'auditPlan', 'assignments.assignedToEmployee'])
                ->get();

            if ($overdueActions->isEmpty()) {
                Log::info('No overdue corrective actions found for reminders');
                return ['success' => true, 'message' => 'No overdue actions found', 'reminders_sent' => 0];
            }

            Log::info('Found overdue corrective actions for reminders', [
                'overdue_count' => $overdueActions->count()
            ]);

            // Group overdue actions by assigned employee using the assigned_to field
            $employeeActions = [];
            foreach ($overdueActions as $action) {
                // Use the assigned_to field from corrective_actions table as primary source
                if ($action->assigned_to) {
                    $employeeId = $action->assigned_to;
                    if (!isset($employeeActions[$employeeId])) {
                        $employeeActions[$employeeId] = [];
                    }
                    $employeeActions[$employeeId][] = $action;
                } else {
                    // Fallback: if no assigned_to, use asset owner (for backward compatibility)
                    $asset = $action->auditAsset->asset;
                    if ($asset && $asset->user_id) {
                        $employeeId = $asset->user_id;
                        if (!isset($employeeActions[$employeeId])) {
                            $employeeActions[$employeeId] = [];
                        }
                        $employeeActions[$employeeId][] = $action;
                    }
                }
            }

            Log::info('Grouped overdue corrective actions by employee', [
                'total_employees' => count($employeeActions),
                'total_overdue_actions' => $overdueActions->count(),
                'employee_actions' => array_map(function ($actions) {
                    return count($actions);
                }, $employeeActions)
            ]);

            $totalReminders = 0;
            $totalEmployees = 0;
            $results = [];

            // Send consolidated reminders to each employee
            foreach ($employeeActions as $employeeId => $employeeActionsList) {
                $reminderResult = $this->sendConsolidatedNotification($employeeId, $employeeActionsList);

                if ($reminderResult['success']) {
                    $totalReminders += $reminderResult['actions_count'];
                    $totalEmployees++;
                }

                $results[] = [
                    'employee_id' => $employeeId,
                    'overdue_actions_count' => count($employeeActionsList),
                    'result' => $reminderResult
                ];
            }

            Log::info('Consolidated overdue corrective action reminders completed', [
                'total_overdue_actions' => $overdueActions->count(),
                'total_employees_notified' => $totalEmployees,
                'total_actions_in_reminders' => $totalReminders,
                'results' => $results
            ]);

            return [
                'success' => true,
                'message' => "Consolidated overdue reminders sent to {$totalEmployees} employee(s) covering {$totalReminders} action(s)",
                'total_overdue_actions' => $overdueActions->count(),
                'total_employees_notified' => $totalEmployees,
                'total_actions_in_reminders' => $totalReminders,
                'results' => $results
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send consolidated overdue corrective action reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send overdue reminders: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notifications for multiple corrective actions
     */
    public function sendBulkNotifications(array $correctiveActionIds): array
    {
        try {
            $actions = CorrectiveAction::whereIn('id', $correctiveActionIds)
                ->with(['auditAsset.asset', 'auditPlan'])
                ->get();

            if ($actions->isEmpty()) {
                return ['success' => false, 'message' => 'No corrective actions found'];
            }

            Log::info('Starting bulk corrective action notifications', [
                'action_ids' => $correctiveActionIds,
                'total_actions' => $actions->count()
            ]);

            // Group corrective actions by assigned employee using the assigned_to field
            $employeeActions = [];
            foreach ($actions as $action) {
                // Use the assigned_to field from corrective_actions table as primary source
                if ($action->assigned_to) {
                    $employeeId = $action->assigned_to;
                    if (!isset($employeeActions[$employeeId])) {
                        $employeeActions[$employeeId] = [];
                    }
                    $employeeActions[$employeeId][] = $action;

                    Log::info('Action assigned to employee via assigned_to field', [
                        'action_id' => $action->id,
                        'assigned_to' => $action->assigned_to,
                        'asset_id' => $action->auditAsset->asset->asset_id ?? 'N/A'
                    ]);
                } else {
                    // Fallback: if no assigned_to, use asset owner (for backward compatibility)
                    $asset = $action->auditAsset->asset;
                    if ($asset && $asset->user_id) {
                        $employeeId = $asset->user_id;
                        if (!isset($employeeActions[$employeeId])) {
                            $employeeActions[$employeeId] = [];
                        }
                        $employeeActions[$employeeId][] = $action;

                        Log::info('Action assigned to employee via asset ownership (fallback)', [
                            'action_id' => $action->id,
                            'employee_id' => $employeeId,
                            'asset_id' => $asset->asset_id ?? 'N/A'
                        ]);
                    } else {
                        Log::warning('Action has no assigned employee or asset owner', [
                            'action_id' => $action->id,
                            'assigned_to' => $action->assigned_to,
                            'asset_user_id' => $asset->user_id ?? 'null'
                        ]);
                    }
                }
            }

            Log::info('Grouped corrective actions by employee', [
                'total_employees' => count($employeeActions),
                'total_actions' => $actions->count(),
                'employee_actions' => array_map(function ($actions) {
                    return count($actions);
                }, $employeeActions),
                'grouping_details' => array_map(function ($actions, $employeeId) {
                    $sampleAction = $actions[0];
                    return [
                        'employee_id' => $employeeId,
                        'actions_count' => count($actions),
                        'sample_action_id' => $sampleAction->id,
                        'assigned_to' => $sampleAction->assigned_to,
                        'asset_id' => $sampleAction->auditAsset->asset->asset_id ?? 'N/A'
                    ];
                }, $employeeActions, array_keys($employeeActions))
            ]);

            $totalSent = 0;
            $totalFailed = 0;
            $results = [];

            // Send consolidated notifications to each employee
            foreach ($employeeActions as $employeeId => $employeeActionsList) {
                $result = $this->sendConsolidatedNotification($employeeId, $employeeActionsList);

                if ($result['success']) {
                    $totalSent++;
                } else {
                    $totalFailed++;
                }

                $results[] = [
                    'employee_id' => $employeeId,
                    'actions_count' => count($employeeActionsList),
                    'result' => $result
                ];
            }

            Log::info('Bulk corrective action notifications completed', [
                'total_actions' => $actions->count(),
                'total_employees' => count($employeeActions),
                'total_sent' => $totalSent,
                'total_failed' => $totalFailed,
                'results' => $results
            ]);

            return [
                'success' => true,
                'message' => "Bulk notifications completed. Sent: {$totalSent}, Failed: {$totalFailed}",
                'total_actions' => $actions->count(),
                'total_employees' => count($employeeActions),
                'total_sent' => $totalSent,
                'total_failed' => $totalFailed,
                'results' => $results
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send bulk corrective action notifications', [
                'action_ids' => $correctiveActionIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send bulk notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send a consolidated notification to an employee with all their corrective actions
     */
    private function sendConsolidatedNotification($employeeId, array $correctiveActions): array
    {
        try {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                Log::warning('Employee not found for consolidated notification', ['employee_id' => $employeeId]);
                return ['success' => false, 'message' => 'Employee not found'];
            }

            // Get the first action to extract common data (audit plan, etc.)
            $firstAction = $correctiveActions[0];
            $auditPlan = $firstAction->auditPlan;

            Log::info('Sending consolidated notification to employee', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'actions_count' => count($correctiveActions)
            ]);

            // Send the consolidated email
            Mail::to($employee->email)->send(new ConsolidatedCorrectiveActionEmail(
                collect($correctiveActions), // Convert array to Collection for Blade template
                $employee,
                $auditPlan
            ));

            Log::info('Consolidated corrective action notification sent successfully', [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'actions_count' => count($correctiveActions)
            ]);

            return [
                'success' => true,
                'message' => "Consolidated notification sent with " . count($correctiveActions) . " actions",
                'actions_count' => count($correctiveActions)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send consolidated corrective action notification to employee', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send consolidated notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send scheduled reminder notifications for pending and in-progress corrective actions
     * Groups actions by employee to send one email per person with all their pending actions
     * Can be called daily/weekly via cron job or manually
     */
    public function sendScheduledReminders(): array
    {
        try {
            $pendingActions = CorrectiveAction::whereIn('status', ['pending', 'in_progress'])
                ->where('due_date', '>=', now()) // Only actions that aren't overdue yet
                ->with(['auditAsset.asset', 'auditPlan'])
                ->get();

            if ($pendingActions->isEmpty()) {
                Log::info('No pending corrective actions found for scheduled reminders');
                return ['success' => true, 'message' => 'No pending actions found', 'reminders_sent' => 0];
            }

            Log::info('Found pending corrective actions for scheduled reminders', [
                'pending_count' => $pendingActions->count()
            ]);

            // Group pending actions by assigned employee using the assigned_to field
            $employeeActions = [];
            foreach ($pendingActions as $action) {
                // Use the assigned_to field from corrective_actions table as primary source
                if ($action->assigned_to) {
                    $employeeId = $action->assigned_to;
                    if (!isset($employeeActions[$employeeId])) {
                        $employeeActions[$employeeId] = [];
                    }
                    $employeeActions[$employeeId][] = $action;
                } else {
                    // Fallback: if no assigned_to, use asset owner (for backward compatibility)
                    $asset = $action->auditAsset->asset;
                    if ($asset && $asset->user_id) {
                        $employeeId = $asset->user_id;
                        if (!isset($employeeActions[$employeeId])) {
                            $employeeActions[$employeeId] = [];
                        }
                        $employeeActions[$employeeId][] = $action;
                    }
                }
            }

            Log::info('Grouped pending corrective actions by employee', [
                'total_employees' => count($employeeActions),
                'total_pending_actions' => $pendingActions->count(),
                'employee_actions' => array_map(function ($actions) {
                    return count($actions);
                }, $employeeActions)
            ]);

            $totalReminders = 0;
            $totalEmployees = 0;
            $results = [];

            // Send consolidated reminders to each employee
            foreach ($employeeActions as $employeeId => $employeeActionsList) {
                $reminderResult = $this->sendConsolidatedNotification($employeeId, $employeeActionsList);

                if ($reminderResult['success']) {
                    $totalReminders += $reminderResult['actions_count'];
                    $totalEmployees++;
                }

                $results[] = [
                    'employee_id' => $employeeId,
                    'pending_actions_count' => count($employeeActionsList),
                    'result' => $reminderResult
                ];
            }

            Log::info('Scheduled corrective action reminders completed', [
                'total_pending_actions' => $pendingActions->count(),
                'total_employees_notified' => $totalEmployees,
                'total_actions_in_reminders' => $totalReminders,
                'results' => $results
            ]);

            return [
                'success' => true,
                'message' => "Scheduled reminders sent to {$totalEmployees} employee(s) covering {$totalReminders} action(s)",
                'total_pending_actions' => $pendingActions->count(),
                'total_employees_notified' => $totalEmployees,
                'total_actions_in_reminders' => $totalReminders,
                'results' => $results
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send scheduled corrective action reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send scheduled reminders: ' . $e->getMessage()
            ];
        }
    }
}
