<?php

namespace Bu\Server\GraphQL\Queries;

use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\AuditAsset;
use Bu\Server\Models\Employee;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\Asset;
use Bu\Server\Models\CorrectiveAction;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeeAuditQueries
{
    /**
     * Get available audit plans for employee selection.
     * 
     * @return array
     */
    public function getAvailableAuditPlans()
    {
        try {
            Log::info("Fetching available audit plans for employee selection");

            // Get all active audit plans (not due yet) with progress
            $allPlans = AuditPlan::where('due_date', '>', Carbon::now()->toDateString())
                ->whereIn('status', ['Planning', 'In Progress'])
                ->orderBy('due_date', 'asc')
                ->get(['id', 'name', 'start_date', 'due_date', 'status']);

            Log::info("Found {$allPlans->count()} active audit plans");

            $auditPlans = $allPlans->map(function ($plan) {
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
                    'status' => $plan->status,
                    'progress' => [
                        'total_assets' => $totalAssets,
                        'audited_assets' => $auditedAssets,
                        'percentage' => $progress
                    ]
                ];
            });

            return $auditPlans->toArray();
        } catch (\Exception $e) {
            Log::error('Available audit plans error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            throw new \Exception('An error occurred while fetching available audit plans: ' . $e->getMessage());
        }
    }

    /**
     * Get employee audit access data using access token.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function getEmployeeAuditAccess($rootValue, array $args)
    {
        try {
            $token = $args['token'];
            Log::info("GraphQL employee audit access with token: {$token}");

            // Get employee info from cache
            $cacheKey = "employee_audit_access:{$token}";
            $employeeData = Cache::get($cacheKey);

            Log::info("Cache data: " . json_encode($employeeData));

            if (!$employeeData) {
                return [
                    'success' => false,
                    'message' => 'Access token expired or invalid.',
                    'employee' => null,
                    'selectedPlan' => null,
                    'auditAssets' => [],
                    'role' => [
                        'isAuditor' => false,
                        'assignedLocation' => null,
                        'canAuditAllAssets' => false,
                        'description' => 'No access'
                    ]
                ];
            }

            // Check if token has expired
            if (Carbon::now()->isAfter($employeeData['expires_at'])) {
                Cache::forget($cacheKey);
                return [
                    'success' => false,
                    'message' => 'Access token has expired.',
                    'employee' => null,
                    'selectedPlan' => null,
                    'auditAssets' => [],
                    'role' => [
                        'isAuditor' => false,
                        'assignedLocation' => null,
                        'canAuditAllAssets' => false,
                        'description' => 'Token expired'
                    ]
                ];
            }

            $employeeId = $employeeData['employee_id'];

            // Get employee details
            $employee = Employee::find($employeeId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Employee not found.',
                    'employee' => null,
                    'selectedPlan' => null,
                    'auditAssets' => [],
                    'role' => [
                        'isAuditor' => false,
                        'assignedLocation' => null,
                        'canAuditAllAssets' => false,
                        'description' => 'Employee not found'
                    ]
                ];
            }

            // Get the specific audit plan from the token
            $auditPlanId = $employeeData['audit_plan_id'] ?? null;

            if (!$auditPlanId) {
                return [
                    'success' => false,
                    'message' => 'Invalid access token: missing audit plan information.',
                    'employee' => null,
                    'selectedPlan' => null,
                    'auditAssets' => [],
                    'role' => [
                        'isAuditor' => false,
                        'assignedLocation' => null,
                        'canAuditAllAssets' => false,
                        'description' => 'Invalid token'
                    ]
                ];
            }

            // Get the specific audit plan details
            try {
                // Check if employee is an auditor for this plan
                $isAuditor = AuditAssignment::where('auditor_id', $employeeId)
                    ->where('audit_plan_id', $auditPlanId)
                    ->exists();

                // If employee is an auditor, check auditor assignments
                // If employee is not an auditor, check if they have assets in this plan
                if ($isAuditor) {
                    $auditPlan = AuditPlan::whereHas('assignments', function ($query) use ($employeeId) {
                        $query->where('auditor_id', $employeeId);
                    })
                        ->where('id', $auditPlanId)
                        ->where('due_date', '>', Carbon::now()->toDateString())
                        ->whereIn('status', ['Planning', 'In Progress'])
                        ->first();
                } else {
                    // For regular employees, check if they have assets in this audit plan
                    $auditPlan = AuditPlan::where('id', $auditPlanId)
                        ->where('due_date', '>', Carbon::now()->toDateString())
                        ->whereIn('status', ['Planning', 'In Progress'])
                        ->whereHas('auditAssets.asset', function ($query) use ($employeeId) {
                            $query->where('user_id', $employeeId);
                        })
                        ->first();
                }

                if (!$auditPlan) {
                    return [
                        'success' => false,
                        'message' => 'Audit plan not found or no longer accessible.',
                        'employee' => null,
                        'selectedPlan' => null,
                        'auditAssets' => [],
                        'role' => [
                            'isAuditor' => false,
                            'assignedLocation' => null,
                            'canAuditAllAssets' => false,
                            'description' => 'Plan not accessible'
                        ]
                    ];
                }

                // Calculate progress and status for the specific plan using audit_status
                $totalAssets = AuditAsset::where('audit_plan_id', $auditPlan->id)->count();
                $auditedAssets = AuditAsset::where('audit_plan_id', $auditPlan->id)
                    ->where('audit_status', true)
                    ->count();

                $progress = $totalAssets > 0 ? round(($auditedAssets / $totalAssets) * 100) : 0;

                $auditPlanData = [
                    'id' => $auditPlan->id,
                    'name' => $auditPlan->name,
                    'start_date' => $auditPlan->start_date,
                    'due_date' => $auditPlan->due_date,
                    'progress' => $progress,
                    'total_assets' => $totalAssets,
                    'completed_assets' => $auditedAssets
                ];

                Log::info("Audit plan data prepared", [
                    'plan_name' => $auditPlan->name,
                    'due_date' => $auditPlan->due_date,
                    'total_assets' => $totalAssets,
                    'completed_assets' => $auditedAssets,
                    'progress' => $progress
                ]);

                Log::info("Found audit plan for employee {$employeeId}: {$auditPlan->name}");
            } catch (\Exception $e) {
                Log::error("Error fetching audit plan: " . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Error fetching audit plan details.',
                    'employee' => null,
                    'selectedPlan' => null,
                    'auditAssets' => [],
                    'role' => [
                        'isAuditor' => false,
                        'assignedLocation' => null,
                        'canAuditAllAssets' => false,
                        'description' => 'Error fetching plan'
                    ]
                ];
            }

            // Determine employee role and access level
            $isAuditor = false;
            $assignedLocation = null;

            // Check if employee is assigned as an auditor for this plan
            $auditAssignment = AuditAssignment::where('audit_plan_id', $auditPlanId)
                ->where('auditor_id', $employeeId)
                ->first();

            if ($auditAssignment) {
                $isAuditor = true;
                $assignedLocation = $auditAssignment->location;
                Log::info("Employee {$employeeId} is an auditor for location: {$assignedLocation->name}");
            } else {
                // Check if employee has any assets assigned to them in this audit plan
                $hasAssignedAssets = Asset::where('user_id', $employeeId)
                    ->whereHas('auditAssets', function ($query) use ($auditPlanId) {
                        $query->where('audit_plan_id', $auditPlanId);
                    })
                    ->exists();

                if ($hasAssignedAssets) {
                    Log::info("Employee {$employeeId} has assigned assets in audit plan {$auditPlanId}");
                } else {
                    Log::info("Employee {$employeeId} has no assigned assets in audit plan {$auditPlanId}");
                }
            }

            // Get audit assets based on employee role
            try {
                if ($isAuditor) {
                    // Auditors see all assets in their assigned location
                    $auditAssets = AuditAsset::where('audit_plan_id', $auditPlanId)
                        ->whereHas('asset', function ($query) use ($assignedLocation) {
                            $query->where('location', $assignedLocation->name);
                        })
                        ->with(['asset'])
                        ->get();

                    Log::info("Auditor {$employeeId} can see {$auditAssets->count()} assets in location {$assignedLocation->name}");
                } else {
                    // Regular employees see only their own assigned assets
                    $auditAssets = AuditAsset::where('audit_plan_id', $auditPlanId)
                        ->whereHas('asset', function ($query) use ($employee) {
                            $query->where('user_id', $employee->id);
                        })
                        ->with(['asset'])
                        ->get();

                    Log::info("Regular employee {$employeeId} can see {$auditAssets->count()} of their assigned assets");
                }

                $auditAssets = $auditAssets->map(function ($auditAsset) {
                    return [
                        'id' => $auditAsset->id,
                        'asset_id' => $auditAsset->asset_id,
                        'asset_type' => $auditAsset->asset->type ?? 'Unknown',
                        'model' => $auditAsset->asset->model ?? 'Unknown',
                        'original_user' => $auditAsset->original_user ?? '',
                        'original_location' => $auditAsset->original_location ?? '',
                        'current_user' => $auditAsset->current_user ?? '',
                        'current_location' => $auditAsset->current_location ?? '',
                        'status' => $auditAsset->current_status ?? 'Pending',
                        'audit_status' => $auditAsset->audit_status ?? false,
                        'notes' => $auditAsset->auditor_notes ?? '',
                        'audited_at' => $auditAsset->audited_at ?? null,
                        'resolved' => $auditAsset->resolved ?? false
                    ];
                });
            } catch (\Exception $e) {
                Log::error("Error fetching audit assets: " . $e->getMessage());
                $auditAssets = collect([]);
            }

            return [
                'success' => true,
                'message' => 'Access granted successfully.',
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email
                ],
                'selectedPlan' => $auditPlanData,
                'auditAssets' => $auditAssets->toArray(),
                'role' => [
                    'isAuditor' => $isAuditor,
                    'assignedLocation' => $assignedLocation ? $assignedLocation->name : null,
                    'canAuditAllAssets' => $isAuditor,
                    'description' => $isAuditor
                        ? "You are an auditor assigned to location: {$assignedLocation->name}. You can see and audit all assets in this location."
                        : "You have assets assigned to you in this audit plan. You can see and update your own assigned assets."
                ]
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL employee audit data access error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'An error occurred while fetching audit data: ' . $e->getMessage(),
                'employee' => null,
                'selectedPlan' => null,
                'auditAssets' => [],
                'role' => [
                    'isAuditor' => false,
                    'assignedLocation' => null,
                    'canAuditAllAssets' => false,
                    'description' => 'Error occurred'
                ]
            ];
        }
    }

    /**
     * Get corrective actions for an employee in an audit plan.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function getCorrectiveActions($rootValue, array $args)
    {
        try {
            $employeeId = $args['employee_id'];
            $auditPlanId = $args['audit_plan_id'];

            Log::info("GraphQL corrective actions - Employee: {$employeeId}, Audit Plan: {$auditPlanId}");

            if (!$employeeId) {
                return [
                    'success' => false,
                    'message' => 'Employee ID is required',
                    'actions' => [],
                    'role' => [
                        'isAuditor' => false,
                        'assignedLocation' => null,
                        'canSeeAllActions' => false,
                        'description' => 'No access'
                    ]
                ];
            }

            if (!$auditPlanId) {
                return [
                    'success' => false,
                    'message' => 'Audit Plan ID is required',
                    'actions' => [],
                    'role' => [
                        'isAuditor' => false,
                        'assignedLocation' => null,
                        'canSeeAllActions' => false,
                        'description' => 'No access'
                    ]
                ];
            }

            // Determine employee role for this audit plan
            $isAuditor = false;
            $assignedLocation = null;

            // Check if employee is assigned as an auditor for this plan
            $auditAssignment = AuditAssignment::where('audit_plan_id', $auditPlanId)
                ->where('auditor_id', $employeeId)
                ->first();

            if ($auditAssignment) {
                $isAuditor = true;
                $assignedLocation = $auditAssignment->location;
                Log::info("Employee {$employeeId} is an auditor for location: {$assignedLocation->name}");
            }

            // Both auditors and regular employees see only corrective actions assigned to them
            // No role restrictions - everyone sees only their assigned actions
            $actions = CorrectiveAction::with(['auditAsset.asset'])
                ->where('assigned_to', $employeeId)
                ->whereHas('auditAsset', function ($query) use ($auditPlanId) {
                    $query->where('audit_plan_id', $auditPlanId);
                })
                ->get();

            Log::info("Found {$actions->count()} corrective actions for employee {$employeeId} in audit plan {$auditPlanId}");

            $actions = $actions->map(function ($action) {
                return [
                    'id' => $action->id,
                    'audit_asset_id' => $action->audit_asset_id,
                    'issue' => $action->issue,
                    'action' => $action->action,
                    'assigned_to' => $action->assigned_to,
                    'priority' => $action->priority,
                    'status' => $action->status,
                    'due_date' => $action->due_date,
                    'completed_date' => $action->completed_date,
                    'notes' => $action->notes,
                    'created_at' => $action->created_at,
                    'updated_at' => $action->updated_at,
                    'asset' => [
                        'asset_id' => $action->auditAsset->asset->asset_id ?? 'N/A',
                        'model' => $action->auditAsset->asset->model ?? 'N/A',
                        'location' => $action->auditAsset->asset->location ?? 'N/A'
                    ]
                ];
            });

            return [
                'success' => true,
                'message' => 'Corrective actions retrieved successfully.',
                'actions' => $actions->toArray(),
                'role' => [
                    'isAuditor' => $isAuditor,
                    'assignedLocation' => $assignedLocation ? $assignedLocation->name : null,
                    'canSeeAllActions' => false, // No one can see all actions anymore
                    'description' => "You can see corrective actions assigned to you for this audit plan."
                ]
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL corrective actions error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Failed to fetch corrective actions: ' . $e->getMessage(),
                'actions' => [],
                'role' => [
                    'isAuditor' => false,
                    'assignedLocation' => null,
                    'canSeeAllActions' => false,
                    'description' => 'Error occurred'
                ]
            ];
        }
    }

    /**
     * Get audit assets for a specific audit plan using access token.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function getAuditPlanAssets($rootValue, array $args)
    {
        try {
            $token = $args['token'];
            $planId = $args['planId'];

            Log::info("GraphQL audit plan assets - Token: {$token}, Plan: {$planId}");

            // Get employee info from cache
            $cacheKey = "employee_audit_access:{$token}";
            $employeeData = Cache::get($cacheKey);

            if (!$employeeData) {
                return [
                    'success' => false,
                    'message' => 'Access token expired or invalid.',
                    'auditPlan' => null,
                    'auditAssets' => [],
                    'progress' => [
                        'total_assets' => 0,
                        'audited_assets' => 0,
                        'percentage' => 0
                    ]
                ];
            }

            // Check if token has expired
            if (Carbon::now()->isAfter($employeeData['expires_at'])) {
                Cache::forget($cacheKey);
                return [
                    'success' => false,
                    'message' => 'Access token has expired.',
                    'auditPlan' => null,
                    'auditAssets' => [],
                    'progress' => [
                        'total_assets' => 0,
                        'audited_assets' => 0,
                        'percentage' => 0
                    ]
                ];
            }

            $employeeId = $employeeData['employee_id'];

            // Verify the audit plan is assigned to this employee
            $auditPlan = AuditPlan::whereHas('assignments', function ($query) use ($employeeId) {
                $query->where('auditor_id', $employeeId);
            })
                ->where('id', $planId)
                ->where('due_date', '>', Carbon::now()->toDateString())
                ->first();

            if (!$auditPlan) {
                return [
                    'success' => false,
                    'message' => 'Audit plan not found or not accessible.',
                    'auditPlan' => null,
                    'auditAssets' => [],
                    'progress' => [
                        'total_assets' => 0,
                        'audited_assets' => 0,
                        'percentage' => 0
                    ]
                ];
            }

            // Get audit assets for this specific plan
            $auditAssets = AuditAsset::where('audit_plan_id', $planId)
                ->with(['asset'])
                ->get();

            // Calculate progress based on audit_status
            $totalAssets = $auditAssets->count();
            $auditedAssets = $auditAssets->where('audit_status', true)->count();
            $progress = $totalAssets > 0 ? round(($auditedAssets / $totalAssets) * 100) : 0;

            Log::info("Found {$totalAssets} assets for audit plan {$planId}, {$auditedAssets} audited, {$progress}% progress");

            $auditAssets = $auditAssets->map(function ($auditAsset) {
                return [
                    'id' => $auditAsset->id,
                    'asset_id' => $auditAsset->asset_id,
                    'asset_type' => $auditAsset->asset->type ?? 'Unknown',
                    'model' => $auditAsset->asset->model ?? 'Unknown',
                    'original_user' => $auditAsset->original_user ?? '',
                    'original_location' => $auditAsset->original_location ?? '',
                    'current_user' => $auditAsset->current_user ?? '',
                    'current_location' => $auditAsset->current_location ?? '',
                    'status' => $auditAsset->current_status ?? 'Pending',
                    'audit_status' => $auditAsset->audit_status ?? false,
                    'notes' => $auditAsset->auditor_notes ?? '',
                    'audited_at' => $auditAsset->audited_at ?? null,
                    'resolved' => $auditAsset->resolved ?? false
                ];
            });

            return [
                'success' => true,
                'message' => 'Audit plan assets retrieved successfully.',
                'auditPlan' => [
                    'id' => $auditPlan->id,
                    'name' => $auditPlan->name,
                    'start_date' => $auditPlan->start_date,
                    'due_date' => $auditPlan->due_date,
                    'status' => $auditPlan->status
                ],
                'auditAssets' => $auditAssets->toArray(),
                'progress' => [
                    'total_assets' => $totalAssets,
                    'audited_assets' => $auditedAssets,
                    'percentage' => $progress
                ]
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL audit plan assets error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'An error occurred while fetching audit plan data: ' . $e->getMessage(),
                'auditPlan' => null,
                'auditAssets' => [],
                'progress' => [
                    'total_assets' => 0,
                    'audited_assets' => 0,
                    'percentage' => 0
                ]
            ];
        }
    }

    /**
     * Get audit history for an employee using access token.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function getAuditHistory($rootValue, array $args)
    {
        try {
            $token = $args['token'];

            Log::info("GraphQL audit history - Token: {$token}");

            // Get employee info from cache
            $cacheKey = "employee_audit_access:{$token}";
            $employeeData = Cache::get($cacheKey);

            if (!$employeeData) {
                return [
                    'success' => false,
                    'message' => 'Access token expired or invalid.',
                    'auditHistory' => []
                ];
            }

            // Check if token has expired
            if (Carbon::now()->isAfter($employeeData['expires_at'])) {
                Cache::forget($cacheKey);
                return [
                    'success' => false,
                    'message' => 'Access token has expired.',
                    'auditHistory' => []
                ];
            }

            $employeeId = $employeeData['employee_id'];

            // Get audit history - assets that have been audited
            $auditHistory = AuditAsset::whereHas('auditPlan.assignments', function ($query) use ($employeeId) {
                $query->where('auditor_id', $employeeId);
            })
                ->whereNotNull('audited_at')
                ->with(['asset', 'auditPlan'])
                ->orderBy('audited_at', 'desc')
                ->get();

            Log::info("Found {$auditHistory->count()} audited assets for employee {$employeeId}");

            $auditHistory = $auditHistory->map(function ($auditAsset) {
                return [
                    'id' => $auditAsset->id,
                    'asset_type' => $auditAsset->asset->type ?? 'Unknown',
                    'model' => $auditAsset->asset->model ?? 'Unknown',
                    'status' => $auditAsset->current_status,
                    'notes' => $auditAsset->auditor_notes,
                    'audited_at' => $auditAsset->audited_at,
                    'audit_plan_name' => $auditAsset->auditPlan->name ?? 'Unknown',
                    'location' => $auditAsset->current_location ?? $auditAsset->original_location,
                    'user' => $auditAsset->current_user ?? $auditAsset->original_user
                ];
            });

            return [
                'success' => true,
                'message' => 'Audit history retrieved successfully.',
                'auditHistory' => $auditHistory->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL audit history error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'An error occurred while fetching audit history: ' . $e->getMessage(),
                'auditHistory' => []
            ];
        }
    }

    /**
     * Get detailed information about a specific audit asset.
     * 
     * @param mixed $rootValue
     * @param array $args
     * @return array
     */
    public function getAuditAssetDetails($rootValue, array $args)
    {
        try {
            $auditAssetId = $args['auditAssetId'];

            Log::info("GraphQL audit asset details - Asset ID: {$auditAssetId}");

            $auditAsset = AuditAsset::with(['asset', 'correctiveActions'])
                ->find($auditAssetId);

            if (!$auditAsset) {
                return [
                    'success' => false,
                    'message' => 'Audit asset not found.',
                    'audit_asset' => null
                ];
            }

            Log::info("Found audit asset {$auditAssetId} with {$auditAsset->correctiveActions->count()} corrective actions");

            $data = [
                'id' => $auditAsset->id,
                'asset_id' => $auditAsset->asset_id,
                'asset_type' => $auditAsset->asset->type ?? 'N/A',
                'model' => $auditAsset->asset->model ?? 'N/A',
                'original_location' => $auditAsset->original_location,
                'original_user' => $auditAsset->original_user,
                'current_status' => $auditAsset->current_status,
                'current_location' => $auditAsset->current_location,
                'current_user' => $auditAsset->current_user,
                'auditor_notes' => $auditAsset->auditor_notes,
                'audited_at' => $auditAsset->audited_at,
                'resolved' => $auditAsset->resolved,
                'audit_status' => $auditAsset->audit_status,
                'audit_summary' => $auditAsset->getAuditSummary(),
                'status_history' => $auditAsset->getStatusTransitionHistory(),
                'corrective_actions' => $auditAsset->correctiveActions->map(function ($action) {
                    return [
                        'id' => $action->id,
                        'issue' => $action->issue,
                        'action' => $action->action,
                        'priority' => $action->priority,
                        'status' => $action->status,
                        'due_date' => $action->due_date,
                        'completed_date' => $action->completed_date,
                        'notes' => $action->notes,
                        'assigned_to' => $action->assigned_to,
                    ];
                }),
            ];

            return [
                'success' => true,
                'message' => 'Audit asset details retrieved successfully.',
                'audit_asset' => $data
            ];
        } catch (\Exception $e) {
            Log::error('GraphQL audit asset details error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Failed to fetch audit asset: ' . $e->getMessage(),
                'audit_asset' => null
            ];
        }
    }
}