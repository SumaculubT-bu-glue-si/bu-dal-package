<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\Employee;
use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\Asset;
use Bu\Server\Models\AuditAsset;
use Bu\Server\Models\CorrectiveAction;
use Bu\Server\Mail\AuditAccessEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmployeeAuditMutations
{
    /**
     * Request access to an audit plan for an employee.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function requestAccess($rootValue, array $args)
    {
        try {
            $email = $args['email'];
            $auditPlanId = $args['audit_plan_id'];

            Log::info("GraphQL request access - Email: {$email}, Audit Plan: {$auditPlanId}");

            // Find employee by email
            Log::info("Looking for employee with email: {$email} for audit plan: {$auditPlanId}");

            $employee = Employee::where('email', $email)->first();

            if (!$employee) {
                Log::warning("Employee not found with email: {$email}");

                // Log all available employees for debugging
                $allEmployees = Employee::all(['id', 'name', 'email']);
                Log::info("Available employees:", $allEmployees->toArray());

                return [
                    'success' => false,
                    'message' => 'Employee not found with this email address.'
                ];
            }

            Log::info("Found employee: {$employee->name} (ID: {$employee->id})");

            // Check if employee has access to the specific audit plan
            Log::info("Checking if employee {$employee->id} has access to audit plan {$auditPlanId}");

            // First check if the audit plan exists and is active
            $auditPlan = AuditPlan::where('id', $auditPlanId)->first();

            if (!$auditPlan) {
                Log::warning("Audit plan {$auditPlanId} not found");
                return [
                    'success' => false,
                    'message' => 'Audit plan not found.'
                ];
            }

            // Log audit plan details for debugging
            Log::info("Audit plan found:", [
                'id' => $auditPlan->id,
                'name' => $auditPlan->name,
                'status' => $auditPlan->status,
                'due_date' => $auditPlan->due_date,
                'current_date' => Carbon::now()->toDateString()
            ]);

            // Check if plan is active (due date in future and status is Planning/In Progress)
            $dueDateCheck = $auditPlan->due_date > Carbon::now()->toDateString();
            $statusCheck = in_array($auditPlan->status, ['Planning', 'In Progress']);

            if (!$dueDateCheck || !$statusCheck) {
                Log::warning("Audit plan {$auditPlanId} not active", [
                    'due_date_check' => $dueDateCheck,
                    'status_check' => $statusCheck,
                    'due_date' => $auditPlan->due_date,
                    'status' => $auditPlan->status
                ]);

                // TEMPORARY: Allow access even if plan is not active (for testing)
                Log::info("Allowing access despite plan status for testing purposes");
            }

            // Check if employee has access to this audit plan
            // Employee can access if they are either:
            // 1. An auditor assigned to this plan, OR
            // 2. Have assets assigned to them in this audit plan
            $isAuditor = AuditAssignment::where('auditor_id', $employee->id)
                ->where('audit_plan_id', $auditPlanId)
                ->exists();

            $hasAssignedAssets = Asset::where('user_id', $employee->id)
                ->whereHas('auditAssets', function ($query) use ($auditPlanId) {
                    $query->where('audit_plan_id', $auditPlanId);
                })
                ->exists();

            $hasAccess = $isAuditor || $hasAssignedAssets;

            Log::info("Employee access check for audit plan {$auditPlanId}:", [
                'employee_id' => $employee->id,
                'employee_email' => $employee->email,
                'is_auditor' => $isAuditor,
                'has_assigned_assets' => $hasAssignedAssets,
                'has_access' => $hasAccess
            ]);

            // TEMPORARY: Allow access if no assignments exist (for testing)
            if (!$hasAccess) {
                Log::warning("Employee {$employee->id} ({$employee->email}) denied access to audit plan {$auditPlanId}");
                Log::info("Access denied - Employee is not an auditor and has no assigned assets in this plan");
                Log::info("Available assignments for this plan:", [
                    'assignments' => AuditAssignment::where('audit_plan_id', $auditPlanId)->get(['auditor_id', 'location_id', 'status'])
                ]);

                // Check if this is a new plan with no assignments yet
                $totalAssignments = AuditAssignment::where('audit_plan_id', $auditPlanId)->count();
                if ($totalAssignments === 0) {
                    Log::info("No assignments found for plan {$auditPlanId}, allowing access for testing");
                    $hasAccess = true;
                } else {
                    return [
                        'success' => false,
                        'message' => 'You do not have access to this audit plan. You must either be assigned as an auditor or have assets assigned to you in this plan.'
                    ];
                }
            }

            // Generate temporary access token
            $accessToken = Str::random(64);
            $expiresAt = Carbon::now()->addMinutes(15); // Token expires in 15 minutes for security

            // Store token in cache with employee and audit plan info
            Cache::put("employee_audit_access:{$accessToken}", [
                'employee_id' => $employee->id,
                'audit_plan_id' => $auditPlanId,
                'expires_at' => $expiresAt
            ], $expiresAt);

            // Send email with temporary access link
            $frontendUrl = config('server.frontend_url', env('FRONTEND_URL', 'http://localhost:9002'));
            $accessUrl = $frontendUrl . "/employee-audits/access/{$accessToken}";

            // Send email to employee with access link
            try {
                Log::info("Attempting to send audit access email to {$email} for plan {$auditPlanId}");

                Mail::to($email)->send(new AuditAccessEmail(
                    $accessUrl,
                    $expiresAt,
                    $employee->name
                ));

                Log::info("Audit access email sent successfully to {$email} for plan {$auditPlanId}", [
                    'access_url' => $accessUrl,
                    'expires_at' => $expiresAt,
                    'employee_name' => $employee->name
                ]);

                return [
                    'success' => true,
                    'message' => 'Access granted! Check your email for the secure access link.',
                    'email_sent' => true,
                    'expiresAt' => $expiresAt->toISOString()
                ];
            } catch (\Exception $emailError) {
                Log::error("Failed to send audit access email to {$email}: " . $emailError->getMessage());
                Log::error("Email error details: " . $emailError->getTraceAsString());

                // Return error but still provide access URL as fallback
                return [
                    'success' => true,
                    'message' => 'Access granted, but email delivery failed. Please contact support.',
                    'email_sent' => false,
                    'accessUrl' => $accessUrl, // Fallback access URL
                    'expiresAt' => $expiresAt->toISOString()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Employee audit access request error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update the status of a corrective action for an employee.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function updateCorrectiveActionStatus($rootValue, array $args)
    {
        try {
            $actionId = $args['action_id'];
            $status = $args['status'];
            $notes = $args['notes'] ?? null;
            $employeeId = $args['employee_id'];

            Log::info("GraphQL update corrective action status - Action: {$actionId}, Status: {$status}, Employee: {$employeeId}");

            // Find the corrective action
            $action = CorrectiveAction::find($actionId);

            if (!$action) {
                return [
                    'success' => false,
                    'message' => 'Corrective action not found',
                    'action' => null
                ];
            }

            // Verify the employee is assigned to this action
            if ($action->assigned_to != $employeeId) {
                return [
                    'success' => false,
                    'message' => 'You are not assigned to this corrective action',
                    'action' => null
                ];
            }

            // Update the action status
            if ($status === 'completed') {
                // Determine the resolution status based on the original issue
                $resolutionStatus = $action->getResolutionStatus();

                // Use the new method that handles completion logic and status updates
                $action->markAsCompleted($notes, $resolutionStatus);
            } else {
                // Regular status update
                $action->status = $status;
                if ($notes) {
                    $action->notes = ($action->notes ? $action->notes . "\n\n" : '') . date('Y-m-d H:i:s') . " - " . $notes;
                }
                $action->save();
            }

            // Load the updated action with relationships
            $updatedAction = CorrectiveAction::with(['auditAsset.asset'])->find($actionId);

            $actionData = [
                'id' => $updatedAction->id,
                'audit_asset_id' => $updatedAction->audit_asset_id,
                'issue' => $updatedAction->issue,
                'action' => $updatedAction->action,
                'assigned_to' => $updatedAction->assigned_to,
                'priority' => $updatedAction->priority,
                'status' => $updatedAction->status,
                'due_date' => $updatedAction->due_date,
                'completed_date' => $updatedAction->completed_date,
                'notes' => $updatedAction->notes,
                'created_at' => $updatedAction->created_at,
                'updated_at' => $updatedAction->updated_at,
                'asset' => [
                    'asset_id' => $updatedAction->auditAsset->asset->asset_id ?? 'N/A',
                    'model' => $updatedAction->auditAsset->asset->model ?? 'N/A',
                    'location' => $updatedAction->auditAsset->asset->location ?? 'N/A'
                ]
            ];

            Log::info("Successfully updated corrective action {$actionId} to status {$status}");

            return [
                'success' => true,
                'message' => 'Action status updated successfully',
                'action' => $actionData
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL update corrective action status error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Failed to update action status: ' . $e->getMessage(),
                'action' => null
            ];
        }
    }

    /**
     * Update asset status for an employee using access token.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function updateAssetStatus($rootValue, array $args)
    {
        try {
            $token = $args['token'];
            $assetId = $args['assetId'];
            $status = $args['status'];
            $notes = $args['notes'] ?? null;
            $reassignUserId = $args['reassignUserId'] ?? null;

            Log::info("GraphQL asset update request received", [
                'token' => $token,
                'assetId' => $assetId,
                'status' => $status,
                'notes' => $notes,
                'reassignUserId' => $reassignUserId
            ]);

            // Get employee info from cache
            $cacheKey = "employee_audit_access:{$token}";
            $employeeData = Cache::get($cacheKey);

            if (!$employeeData) {
                return [
                    'success' => false,
                    'message' => 'Access token expired or invalid.',
                    'asset' => null,
                    'main_asset_updated' => false,
                    'user_assignment' => null,
                    'changes_detected' => [
                        'location' => false,
                        'user' => false
                    ]
                ];
            }

            // Check if token has expired
            if (Carbon::now()->isAfter($employeeData['expires_at'])) {
                Cache::forget($cacheKey);
                return [
                    'success' => false,
                    'message' => 'Access token has expired.',
                    'asset' => null,
                    'main_asset_updated' => false,
                    'user_assignment' => null,
                    'changes_detected' => [
                        'location' => false,
                        'user' => false
                    ]
                ];
            }

            $employeeId = $employeeData['employee_id'];

            // Get employee details for tracking
            $employee = Employee::find($employeeId);

            // Find the audit asset and verify the employee has access to it
            $auditAsset = AuditAsset::where('id', $assetId)
                ->whereHas('auditPlan', function ($query) {
                    $query->where('due_date', '>', Carbon::now()->toDateString())
                        ->whereIn('status', ['Planning', 'In Progress']);
                })
                ->first();

            if (!$auditAsset) {
                return [
                    'success' => false,
                    'message' => 'Asset not found or audit plan is no longer active.',
                    'asset' => null,
                    'main_asset_updated' => false,
                    'user_assignment' => null,
                    'changes_detected' => [
                        'location' => false,
                        'user' => false
                    ]
                ];
            }

            // Now determine if the employee is an auditor for this audit plan
            $isAuditor = AuditAssignment::where('audit_plan_id', $auditAsset->audit_plan_id)
                ->where('auditor_id', $employeeId)
                ->exists();

            // Verify access based on role
            if ($isAuditor) {
                // Auditors can update any asset in their assigned audit plan
                $hasAccess = AuditAsset::where('id', $assetId)
                    ->whereHas('auditPlan.assignments', function ($query) use ($employeeId) {
                        $query->where('auditor_id', $employeeId);
                    })
                    ->exists();
            } else {
                // Regular employees can only update assets assigned to them
                $hasAccess = AuditAsset::where('id', $assetId)
                    ->whereHas('asset', function ($query) use ($employeeId) {
                        $query->where('user_id', $employeeId);
                    })
                    ->exists();
            }

            if (!$hasAccess) {
                return [
                    'success' => false,
                    'message' => 'You do not have access to update this asset.',
                    'asset' => null,
                    'main_asset_updated' => false,
                    'user_assignment' => null,
                    'changes_detected' => [
                        'location' => false,
                        'user' => false
                    ]
                ];
            }

            // Check if asset is already resolved
            if ($auditAsset->resolved) {
                return [
                    'success' => false,
                    'message' => 'This asset has already been resolved and cannot be updated.',
                    'asset' => null,
                    'main_asset_updated' => false,
                    'user_assignment' => null,
                    'changes_detected' => [
                        'location' => false,
                        'user' => false
                    ]
                ];
            }

            // Get the main asset for comparison and updates
            $mainAsset = Asset::find($auditAsset->asset_id);

            // Check if asset location or user has changed since audit plan creation
            $locationChanged = $mainAsset && $mainAsset->location !== $auditAsset->original_location;
            $userChanged = $mainAsset && $mainAsset->user_id !== $auditAsset->original_user;

            // Update the audit asset
            $auditAsset->update([
                'current_status' => $status,
                'auditor_notes' => $notes,
                'audited_at' => Carbon::now(),
                'audit_status' => true, // Mark as audited
                'audited_by' => $employee->name // Track who audited this asset
            ]);

            // Handle user assignment if requested
            $userAssigned = false;
            $oldUserId = null;
            $newUserName = null;

            if ($reassignUserId) {
                // Only allow user assignment if the asset currently has no user
                if ($mainAsset->user_id || $auditAsset->current_user) {
                    Log::warning("User assignment rejected - asset already has user", [
                        'audit_asset_id' => $auditAsset->id,
                        'asset_id' => $mainAsset->id,
                        'main_asset_user_id' => $mainAsset->user_id,
                        'audit_asset_current_user' => $auditAsset->current_user,
                        'requested_user_id' => $reassignUserId
                    ]);

                    return [
                        'success' => false,
                        'message' => 'Cannot assign user to asset that already has a user assigned.',
                        'asset' => null,
                        'main_asset_updated' => false,
                        'user_assignment' => null,
                        'changes_detected' => [
                            'location' => false,
                            'user' => false
                        ]
                    ];
                }

                // Get the new user details
                $newUser = Employee::find($reassignUserId);
                if ($newUser) {
                    $oldUserId = $mainAsset->user_id; // This will be null
                    $newUserName = $newUser->name;
                    $userAssigned = true;

                    // Update the audit asset's current_user with the employee name
                    $auditAsset->update([
                        'current_user' => $newUserName
                    ]);

                    Log::info("User assigned to audit asset", [
                        'audit_asset_id' => $auditAsset->id,
                        'old_user_id' => $oldUserId,
                        'new_user_id' => $reassignUserId,
                        'new_user_name' => $newUserName
                    ]);
                }
            }

            // Also update the main asset in the assets table
            $mainAssetUpdated = false;
            if ($mainAsset) {
                $oldStatus = $mainAsset->status;
                $updateData = [
                    'status' => $status,
                    'updated_at' => Carbon::now()
                ];

                // If user was assigned, update the main asset table
                if ($userAssigned) {
                    $updateData['user_id'] = $reassignUserId; // Update to new user ID in main assets table
                }

                $mainAsset->update($updateData);
                $mainAssetUpdated = true;

                Log::info("Main asset updated", [
                    'asset_id' => $mainAsset->id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'user_assigned' => $userAssigned,
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $reassignUserId,
                    'updated_by' => 'audit_system'
                ]);
            } else {
                Log::warning("Main asset not found for audit asset", [
                    'audit_asset_id' => $auditAsset->id,
                    'asset_id' => $auditAsset->asset_id
                ]);
            }

            Log::info("Asset status updated by employee", [
                'employee_id' => $employeeId,
                'employee_name' => $employee->name,
                'asset_id' => $assetId,
                'audit_asset_id' => $auditAsset->id,
                'old_status' => $auditAsset->getOriginal('current_status'),
                'new_status' => $status,
                'notes' => $notes,
                'audit_status' => $auditAsset->audit_status,
                'main_asset_updated' => $mainAssetUpdated,
                'location_changed' => $locationChanged,
                'user_changed' => $userChanged,
                'user_assigned' => $userAssigned,
                'original_location' => $auditAsset->original_location,
                'current_location' => $auditAsset->current_location ?? 'Not tracked',
                'original_user' => $auditAsset->original_user,
                'current_user' => $auditAsset->current_user ?? 'Not tracked'
            ]);

            return [
                'success' => true,
                'message' => $userAssigned
                    ? 'Asset status updated and user assigned successfully in both audit_assets and assets tables.'
                    : 'Asset status updated successfully in both audit_assets and assets tables.',
                'asset' => [
                    'id' => $auditAsset->id,
                    'audit_status' => $auditAsset->audit_status,
                    'current_status' => $auditAsset->current_status,
                    'notes' => $auditAsset->auditor_notes,
                    'audited_at' => $auditAsset->audited_at,
                    'audited_by' => $auditAsset->audited_by,
                    'location_changed' => $locationChanged,
                    'user_changed' => $userChanged,
                    'user_assigned' => $userAssigned
                ],
                'main_asset_updated' => $mainAssetUpdated,
                'user_assignment' => $userAssigned ? [
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $reassignUserId,
                    'new_user_name' => $newUserName
                ] : null,
                'changes_detected' => [
                    'location' => $locationChanged,
                    'user' => $userChanged
                ]
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL asset update error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'An error occurred while updating the asset: ' . $e->getMessage(),
                'asset' => null,
                'main_asset_updated' => false,
                'user_assignment' => null,
                'changes_detected' => [
                    'location' => false,
                    'user' => false
                ]
            ];
        }
    }

    /**
     * Send scheduled reminders for pending and in-progress corrective actions.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function sendScheduledReminders($rootValue, array $args)
    {
        try {
            Log::info("GraphQL scheduled reminders - Starting reminder process");

            // Send scheduled reminders using the notification service
            $notificationService = new \Bu\Server\Services\CorrectiveActionNotificationService();
            $result = $notificationService->sendScheduledReminders();

            Log::info('GraphQL scheduled corrective action reminders sent', [
                'result' => $result
            ]);

            // Transform the result to match our GraphQL response format
            return [
                'success' => $result['success'] ?? true,
                'message' => $result['message'] ?? 'Scheduled reminders processed successfully.',
                'reminders_sent' => $result['reminders_sent'] ?? 0,
                'actions_processed' => $result['actions_processed'] ?? 0,
                'details' => [
                    'pending_reminders' => $result['details']['pending_reminders'] ?? 0,
                    'in_progress_reminders' => $result['details']['in_progress_reminders'] ?? 0,
                    'overdue_reminders' => $result['details']['overdue_reminders'] ?? 0,
                    'employees_notified' => $result['details']['employees_notified'] ?? [],
                    'errors' => $result['details']['errors'] ?? []
                ]
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL scheduled reminders error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error sending scheduled reminders: ' . $e->getMessage(),
                'reminders_sent' => 0,
                'actions_processed' => 0,
                'details' => [
                    'pending_reminders' => 0,
                    'in_progress_reminders' => 0,
                    'overdue_reminders' => 0,
                    'employees_notified' => [],
                    'errors' => [$e->getMessage()]
                ]
            ];
        }
    }

    /**
     * Send manual reminders for specific corrective actions.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function sendManualReminders($rootValue, array $args)
    {
        try {
            $actionIds = $args['actionIds'];

            Log::info("GraphQL manual reminders - Action IDs: " . implode(', ', $actionIds));

            // Get the corrective actions
            $correctiveActions = \Bu\Server\Models\CorrectiveAction::whereIn('id', $actionIds)
                ->with(['auditAsset.asset', 'auditPlan'])
                ->get();

            if ($correctiveActions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No corrective actions found for the provided IDs.',
                    'reminders_sent' => 0,
                    'actions_processed' => 0,
                    'details' => [
                        'employees_notified' => [],
                        'actions_processed' => [],
                        'errors' => ['No corrective actions found']
                    ]
                ];
            }

            // Send bulk notifications for all actions
            $notificationService = new \Bu\Server\Services\CorrectiveActionNotificationService();
            $result = $notificationService->sendBulkNotifications($actionIds);

            Log::info('sendBulkNotifications result:', $result);

            if ($result['success']) {
                // Extract employee names from the results
                $employeesNotified = [];
                if (isset($result['results'])) {
                    foreach ($result['results'] as $resultItem) {
                        if ($resultItem['result']['success']) {
                            $employee = \Bu\Server\Models\Employee::find($resultItem['employee_id']);
                            if ($employee) {
                                $employeesNotified[] = $employee->name;
                            }
                        }
                    }
                }

                $response = [
                    'success' => true,
                    'message' => $result['message'] ?? 'Manual reminders sent successfully',
                    'reminders_sent' => $result['total_sent'] ?? 0,
                    'actions_processed' => $result['total_actions'] ?? count($actionIds),
                    'details' => [
                        'employees_notified' => $employeesNotified,
                        'actions_processed' => $actionIds,
                        'errors' => []
                    ]
                ];

                Log::info('sendManualReminders response:', $response);
                return $response;
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send manual reminders',
                    'reminders_sent' => 0,
                    'actions_processed' => 0,
                    'details' => [
                        'employees_notified' => [],
                        'actions_processed' => [],
                        'errors' => [$result['message'] ?? 'Unknown error']
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error('GraphQL manual reminders error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Error sending manual reminders: ' . $e->getMessage(),
                'reminders_sent' => 0,
                'actions_processed' => 0,
                'details' => [
                    'employees_notified' => [],
                    'actions_processed' => [],
                    'errors' => [$e->getMessage()]
                ]
            ];
        }
    }
}
