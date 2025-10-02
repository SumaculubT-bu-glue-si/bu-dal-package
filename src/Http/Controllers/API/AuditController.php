<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\Employee;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\AuditAsset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Bu\Server\Services\AuditEmailService;
use Bu\Server\Http\Requests\AuditPlanRequest;

class AuditController extends ApiController
{
    /**
     * Request access to an audit
     */
    public function requestAccess(Request $request)
    {
        try {
            Log::info("Request data received: " . json_encode($request->all()));

            $request->validate([
                'email' => 'required|email',
                'audit_plan_id' => 'required|string'
            ]);

            $email = $request->input('email');
            $auditPlanId = $request->input('audit_plan_id');

            $employee = Employee::where('email', $email)->first();

            if (!$employee) {
                Log::warning("Employee not found with email: {$email}");
                return $this->errorResponse('Employee not found with this email address.', 404);
            }

            $auditPlan = AuditPlan::find($auditPlanId);

            if (!$auditPlan) {
                Log::warning("Audit plan {$auditPlanId} not found");
                return $this->errorResponse('Audit plan not found.', 404);
            }

            // Generate access token
            $token = Str::random(64);
            $expiresAt = Carbon::now()->addMinutes(15);

            // Store the token data in cache
            Cache::put("audit_access:{$token}", [
                'employee_id' => $employee->id,
                'audit_plan_id' => $auditPlan->id,
                'expires_at' => $expiresAt
            ], $expiresAt);

            // Send email notification
            $emailService = new AuditEmailService();
            $emailSent = $emailService->sendAccessEmail($employee, $auditPlan, $token, $expiresAt);

            return $this->successResponse([
                'token' => $token,
                'expires_at' => $expiresAt,
                'employee' => $employee,
                'audit_plan' => $auditPlan,
                'email_sent' => $emailSent,
                'accessUrl' => $emailSent ? null : url("/employee-audits/access/{$token}")
            ], 'Access request processed successfully');
        } catch (\Exception $e) {
            Log::error("Error in requestAccess: " . $e->getMessage());
            return $this->errorResponse('An error occurred while processing your request.', 500);
        }
    }

    /**
     * Validate access token and return audit plan data
     */
    public function validateAccessToken(Request $request, string $token)
    {
        try {
            $tokenData = Cache::get("audit_access:{$token}");

            if (!$tokenData) {
                return $this->errorResponse('Access token is invalid or has expired.', 401);
            }

            if (Carbon::parse($tokenData['expires_at'])->isPast()) {
                Cache::forget("audit_access:{$token}");
                return $this->errorResponse('Access token has expired.', 401);
            }

            $employee = Employee::find($tokenData['employee_id']);
            $auditPlan = AuditPlan::find($tokenData['audit_plan_id']);

            if (!$employee || !$auditPlan) {
                return $this->errorResponse('Employee or audit plan not found.', 404);
            }

            // Check if employee is assigned as an auditor for this audit plan
            $auditAssignment = AuditAssignment::where('audit_plan_id', $auditPlan->id)
                ->where('auditor_id', $employee->id)
                ->first();

            $isAuditor = $auditAssignment !== null;
            $assignedLocation = $auditAssignment ? $auditAssignment->location->name : null;

            // Get audit assets for this plan with eager loading of asset relationship
            $auditAssets = AuditAsset::where('audit_plan_id', $auditPlan->id)
                ->with(['asset'])
                ->get()
                ->map(function ($auditAsset) {
                    $asset = $auditAsset->asset;
                    return [
                        'id' => $auditAsset->id,
                        'asset_id' => $asset ? $asset->asset_id : $auditAsset->asset_id,
                        'asset_type' => $asset ? $asset->type : 'Unknown',
                        'model' => $asset ? $asset->model : 'Unknown',
                        'manufacturer' => $asset ? $asset->manufacturer : 'Unknown',
                        'hostname' => $asset ? $asset->hostname : 'Unknown',
                        'original_location' => $auditAsset->original_location,
                        'original_user' => $auditAsset->original_user,
                        'current_location' => $auditAsset->current_location,
                        'current_user' => $auditAsset->current_user,
                        'status' => $auditAsset->current_status,
                        'notes' => $auditAsset->auditor_notes,
                        'audited_at' => $auditAsset->audited_at,
                        'resolved' => $auditAsset->resolved,
                        'audit_status' => $auditAsset->audit_status,
                        'asset_details' => $asset ? [
                            'serial_number' => $asset->serial_number,
                            'part_number' => $asset->part_number,
                            'form_factor' => $asset->form_factor,
                            'os' => $asset->os,
                            'purchase_date' => $asset->purchase_date,
                            'status' => $asset->status
                        ] : null
                    ];
                });

            return $this->successResponse([
                'employee' => $employee,
                'audit_plan' => $auditPlan,
                'expires_at' => $tokenData['expires_at'],
                'audit_assets' => $auditAssets,
                'role' => [
                    'isAuditor' => $isAuditor,
                    'assignedLocation' => $assignedLocation,
                    'canAuditAllAssets' => $isAuditor,
                    'description' => $isAuditor
                        ? "You are auditing all assets in location: {$assignedLocation}. You can see and update all assets in this location."
                        : "You have assets assigned to you in this audit plan. You can see and update your own assigned assets."
                ]
            ], 'Access token validated successfully');
        } catch (\Exception $e) {
            Log::error("Error in validateAccessToken: " . $e->getMessage());
            return $this->errorResponse('An error occurred while validating your access.', 500);
        }
    }

    /**
     * Get all audit plans
     */
    public function getPlans(Request $request)
    {
        $plans = AuditPlan::with(['locations', 'auditors'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($plans);
    }

    /**
     * Get available audit plans with progress information
     */
    public function getAvailablePlans()
    {
        try {
            Log::info("Fetching available audit plans for employee selection");

            // Get all audit plans first
            $allPlans = AuditPlan::get(['id', 'name', 'start_date', 'due_date', 'status']);

            Log::info("Total audit plans before filtering: " . $allPlans->count(), [
                'plans' => $allPlans->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'status' => $p->status,
                    'due_date' => $p->due_date
                ])->toArray()
            ]);

            // Now filter them
            $activePlans = $allPlans->filter(function ($plan) {
                $isActive = $plan->due_date > Carbon::now()->toDateString()
                    && in_array($plan->status, ['Planning', 'In Progress']);

                if (!$isActive) {
                    Log::info("Plan {$plan->name} filtered out:", [
                        'id' => $plan->id,
                        'status' => $plan->status,
                        'due_date' => $plan->due_date,
                        'now' => Carbon::now()->toDateString(),
                        'status_ok' => in_array($plan->status, ['Planning', 'In Progress']),
                        'date_ok' => $plan->due_date > Carbon::now()->toDateString()
                    ]);
                }

                return $isActive;
            });

            Log::info("Found {$activePlans->count()} active audit plans");

            $auditPlans = $activePlans->map(function ($plan) {
                // Calculate progress for this plan
                $totalAssets = AuditAsset::where('audit_plan_id', $plan->id)->count();
                $auditedAssets = AuditAsset::where('audit_plan_id', $plan->id)
                    ->where('audit_status', true)
                    ->count();
                $progress = $totalAssets > 0 ? round(($auditedAssets / $totalAssets) * 100) : 0;

                Log::info("Plan {$plan->name} (ID: {$plan->id}): {$totalAssets} total assets, {$auditedAssets} audited, {$progress}% progress");

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'start_date' => $plan->start_date,
                    'due_date' => $plan->due_date,
                    'progress' => [
                        'total_assets' => $totalAssets,
                        'audited_assets' => $auditedAssets,
                        'percentage' => $progress
                    ]
                ];
            });

            return $this->successResponse([
                'auditPlans' => $auditPlans
            ]);
        } catch (\Exception $e) {
            Log::error('Available audit plans error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return $this->errorResponse(
                'An error occurred while fetching available audit plans: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Create a new audit plan
     */
    public function createPlan(AuditPlanRequest $request)
    {
        try {
            $plan = AuditPlan::create($request->validated());

            if ($request->has('locations')) {
                $plan->locations()->sync($request->input('locations'));
            }

            if ($request->has('auditors')) {
                $plan->auditors()->sync($request->input('auditors'));
            }

            // Send notifications to all relevant parties
            try {
                $notificationService = new \Bu\Server\Services\AuditNotificationService();
                $notificationsSent = $notificationService->sendInitialNotifications(
                    $plan,
                    $request->input('auditors', []),
                    $request->input('locations', [])
                );

                Log::info('Audit plan created and notifications sent', [
                    'audit_plan_id' => $plan->id,
                    'notifications_sent' => $notificationsSent
                ]);
            } catch (\Exception $e) {
                // Log notification error but don't fail the request
                Log::error('Failed to send notifications for new audit plan', [
                    'audit_plan_id' => $plan->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $this->successResponse(
                $plan->load(['locations', 'auditors']),
                'Audit plan created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error creating audit plan', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Failed to create audit plan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific audit plan
     */
    public function getPlan(int $id)
    {
        $plan = AuditPlan::with(['locations', 'auditors', 'assignments', 'auditAssets'])
            ->findOrFail($id);

        return $this->successResponse($plan);
    }

    /**
     * Update an audit plan
     */
    public function updatePlan(AuditPlanRequest $request, int $id)
    {
        $plan = AuditPlan::findOrFail($id);
        $plan->update($request->validated());

        if ($request->has('locations')) {
            $plan->locations()->sync($request->input('locations'));
        }

        if ($request->has('auditors')) {
            $plan->auditors()->sync($request->input('auditors'));
        }

        return $this->successResponse(
            $plan->load(['locations', 'auditors']),
            'Audit plan updated successfully'
        );
    }

    /**
     * Delete an audit plan
     */
    public function deletePlan(int $id)
    {
        $plan = AuditPlan::findOrFail($id);
        $plan->delete();

        return $this->successResponse(null, 'Audit plan deleted successfully');
    }

    /**
     * Get audit assignments
     */
    public function getAssignments(Request $request)
    {
        $assignments = AuditAssignment::with(['auditor', 'location', 'auditPlan'])
            ->when($request->plan_id, function ($query, $planId) {
                return $query->where('audit_plan_id', $planId);
            })
            ->when($request->auditor_id, function ($query, $auditorId) {
                return $query->where('auditor_id', $auditorId);
            })
            ->get();

        return $this->successResponse($assignments);
    }

    /**
     * Update an assignment
     */
    public function updateAssignment(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'notes' => 'nullable|string'
        ]);

        $assignment = AuditAssignment::findOrFail($id);
        $assignment->update($request->only(['status', 'notes']));

        return $this->successResponse($assignment->load(['auditor', 'location', 'auditPlan']));
    }

    /**
     * Get audit asset details
     */
    public function getAuditAsset(int $auditAssetId)
    {
        $auditAsset = AuditAsset::with(['asset', 'auditPlan'])
            ->findOrFail($auditAssetId);

        return $this->successResponse($auditAsset);
    }

    /**
     * Get statistics for audits
     */
    public function statistics(Request $request)
    {
        $query = AuditPlan::query();

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        $stats = [
            'total_plans' => $query->count(),
            'completed_plans' => $query->where('status', 'completed')->count(),
            'in_progress_plans' => $query->where('status', 'in_progress')->count(),
            'assets_audited' => AuditAsset::where('audit_status', true)->count(),
            'pending_assets' => AuditAsset::where('audit_status', false)->count()
        ];

        return $this->successResponse($stats);
    }

    /**
     * Handle asset status update from employee audit
     */
    public function handleAssetUpdate(Request $request, string $token)
    {
        try {
            Log::info("Handling asset update with token: " . $token);
            Log::info("Request data:", $request->all());

            // Validate token
            $tokenData = Cache::get("audit_access:{$token}");
            if (!$tokenData) {
                return $this->errorResponse('Access token is invalid or has expired.', 401);
            }

            if (Carbon::parse($tokenData['expires_at'])->isPast()) {
                Cache::forget("audit_access:{$token}");
                return $this->errorResponse('Access token has expired.', 401);
            }

            // Validate request data
            $request->validate([
                'assetId' => 'required|exists:audit_assets,id',
                'status' => 'required|string',
                'notes' => 'nullable|string',
                'reassignUserId' => 'nullable|exists:employees,id'
            ]);

            // Find the audit asset
            $auditAsset = AuditAsset::findOrFail($request->assetId);

            // Verify the asset belongs to the plan in the token
            if ($auditAsset->audit_plan_id != $tokenData['audit_plan_id']) {
                return $this->errorResponse('Unauthorized to update this asset.', 403);
            }

            // Update the asset
            $auditAsset->update([
                'current_status' => $request->status,
                'auditor_notes' => $request->notes,
                'current_user' => $request->reassignUserId ?? $auditAsset->current_user,
                'audited_at' => now(),
                'audit_status' => true
            ]);

            Log::info("Asset updated successfully:", [
                'asset_id' => $auditAsset->id,
                'new_status' => $request->status,
                'audit_plan_id' => $auditAsset->audit_plan_id
            ]);

            return $this->successResponse([
                'asset' => $auditAsset->fresh()->load('asset'),
                'message' => 'Asset status updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating asset status: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->errorResponse('Failed to update asset status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update corrective action status
     *
     * @param Request $request
     * @param string|null $token Optional token for authenticated updates
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActionStatus(Request $request, string $token = null)
    {
        try {
            Log::info("Updating corrective action status:", $request->all());

            if ($token) {
                // Validate token
                $tokenData = Cache::get("audit_access:{$token}");
                if (!$tokenData) {
                    return $this->errorResponse('Access token is invalid or has expired.', 401);
                }

                if (Carbon::parse($tokenData['expires_at'])->isPast()) {
                    Cache::forget("audit_access:{$token}");
                    return $this->errorResponse('Access token has expired.', 401);
                }

                // Validate request data
                $request->validate([
                    'action_id' => 'required|exists:corrective_actions,id',
                    'status' => 'required|in:open,in_progress,completed,verified',
                    'comment' => 'required|string',
                    'employee_id' => 'required|exists:employees,id'
                ]);

                // Create update record and update status
                $action = \Bu\Server\Models\CorrectiveAction::findOrFail($request->action_id);

                // Verify the employee is authorized to update this action
                if ($action->assigned_to != $request->employee_id) {
                    return $this->errorResponse('You are not authorized to update this action.', 403);
                }

                $update = $action->updates()->create([
                    'status' => $request->status,
                    'comment' => $request->comment,
                    'user_id' => $request->employee_id
                ]);
            } else {
                // Regular update flow without token
                $request->validate([
                    'action_id' => 'required|exists:corrective_actions,id',
                    'status' => 'required|in:pending,in_progress,completed,overdue',
                    'notes' => 'nullable|string',
                    'employee_id' => 'required|exists:employees,id'
                ]);

                $action = \Bu\Server\Models\CorrectiveAction::findOrFail($request->action_id);

                // Verify the action is assigned to this employee
                if ($action->assigned_to !== $request->employee_id) {
                    return $this->errorResponse('Unauthorized to update this action.', 403);
                }
            }

            $action->update([
                'status' => $request->status,
                'notes' => $request->notes ?? null,
                'completed_date' => in_array($request->status, ['completed', 'verified']) ? now() : null
            ]);

            Log::info("Corrective action updated successfully:", [
                'action_id' => $action->id,
                'new_status' => $request->status,
                'employee_id' => $request->employee_id
            ]);

            return $this->successResponse([
                'action' => $action->fresh(),
                'update' => $update ?? null,
                'message' => 'Action status updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating corrective action status: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->errorResponse('Failed to update action status: ' . $e->getMessage(), 500);
        }
    }
}