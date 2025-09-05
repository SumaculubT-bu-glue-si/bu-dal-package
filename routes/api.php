<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use Bu\DAL\Models\Asset;
use Bu\DAL\Models\Employee;
use Bu\DAL\Models\Location;
use Bu\DAL\Models\Project;

use Bu\DAL\Models\AuditAssignment;
use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Models\CorrectiveAction;
use Bu\DAL\Models\CorrectiveActionAssignment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// GraphQL endpoint - Single API entry point with custom CORS
Route::post('/graphql', \Nuwave\Lighthouse\Http\GraphQLController::class)->middleware(\App\Http\Middleware\GraphQLCors::class);

// Optional: GraphQL playground for development (if you want to test queries)
Route::get('/graphql-playground', function () {
    return view('graphql-playground');
});

// Employee audit access request
Route::post('/employee-audits/request-access', function (Request $request) {
    try {
        \Illuminate\Support\Facades\Log::info("Request data received: " . json_encode($request->all()));
        \Illuminate\Support\Facades\Log::info("Request headers: " . json_encode($request->headers->all()));

        $request->validate([
            'email' => 'required|email',
            'audit_plan_id' => 'required|string'
        ]);

        $email = $request->input('email');
        $auditPlanId = $request->input('audit_plan_id');

        // Find employee by email
        \Illuminate\Support\Facades\Log::info("Looking for employee with email: {$email} for audit plan: {$auditPlanId}");

        $employee = Employee::where('email', $email)->first();

        if (!$employee) {
            \Illuminate\Support\Facades\Log::warning("Employee not found with email: {$email}");

            // Log all available employees for debugging
            $allEmployees = Employee::all(['id', 'name', 'email']);
            \Illuminate\Support\Facades\Log::info("Available employees:", $allEmployees->toArray());

            return response()->json([
                'success' => false,
                'message' => 'Employee not found with this email address.'
            ], 404);
        }

        \Illuminate\Support\Facades\Log::info("Found employee: {$employee->name} (ID: {$employee->id})");

        // Check if employee has access to the specific audit plan
        \Illuminate\Support\Facades\Log::info("Checking if employee {$employee->id} has access to audit plan {$auditPlanId}");

        // First check if the audit plan exists and is active
        $auditPlan = AuditPlan::where('id', $auditPlanId)->first();

        if (!$auditPlan) {
            \Illuminate\Support\Facades\Log::warning("Audit plan {$auditPlanId} not found");
            return response()->json([
                'success' => false,
                'message' => 'Audit plan not found.'
            ], 404);
        }

        // Log audit plan details for debugging
        \Illuminate\Support\Facades\Log::info("Audit plan found:", [
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
            \Illuminate\Support\Facades\Log::warning("Audit plan {$auditPlanId} not active", [
                'due_date_check' => $dueDateCheck,
                'status_check' => $statusCheck,
                'due_date' => $auditPlan->due_date,
                'status' => $auditPlan->status
            ]);

            // TEMPORARY: Allow access even if plan is not active (for testing)
            \Illuminate\Support\Facades\Log::info("Allowing access despite plan status for testing purposes");
        }

        // Check if employee has access to this audit plan
        // Employee can access if they are either:
        // 1. An auditor assigned to this plan, OR
        // 2. Have assets assigned to them in this audit plan
        $isAuditor = AuditAssignment::where('auditor_id', $employee->id)
            ->where('audit_plan_id', $auditPlanId)
            ->exists();

        $hasAssignedAssets = \Bu\DAL\Models\Asset::where('user_id', $employee->id)
            ->whereHas('auditAssets', function ($query) use ($auditPlanId) {
                $query->where('audit_plan_id', $auditPlanId);
            })
            ->exists();

        $hasAccess = $isAuditor || $hasAssignedAssets;

        \Illuminate\Support\Facades\Log::info("Employee access check for audit plan {$auditPlanId}:", [
            'employee_id' => $employee->id,
            'employee_email' => $employee->email,
            'is_auditor' => $isAuditor,
            'has_assigned_assets' => $hasAssignedAssets,
            'has_access' => $hasAccess
        ]);

        // TEMPORARY: Allow access if no assignments exist (for testing)
        if (!$hasAccess) {
            \Illuminate\Support\Facades\Log::warning("Employee {$employee->id} ({$employee->email}) denied access to audit plan {$auditPlanId}");
            \Illuminate\Support\Facades\Log::info("Access denied - Employee is not an auditor and has no assigned assets in this plan");
            \Illuminate\Support\Facades\Log::info("Available assignments for this plan:", [
                'assignments' => AuditAssignment::where('audit_plan_id', $auditPlanId)->get(['auditor_id', 'location_id', 'status'])
            ]);

            // Check if this is a new plan with no assignments yet
            $totalAssignments = AuditAssignment::where('audit_plan_id', $auditPlanId)->count();
            if ($totalAssignments === 0) {
                \Illuminate\Support\Facades\Log::info("No assignments found for plan {$auditPlanId}, allowing access for testing");
                $hasAccess = true;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this audit plan. You must either be assigned as an auditor or have assets assigned to you in this plan.'
                ], 403);
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
        $accessUrl = 'http://localhost:9002' . "/employee-audits/access/{$accessToken}";

        // Send email to employee with access link
        try {
            \Illuminate\Support\Facades\Log::info("Attempting to send audit access email to {$email} for plan {$auditPlanId}");

            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\AuditAccessEmail(
                $accessUrl,
                $expiresAt,
                $employee->name
            ));

            \Illuminate\Support\Facades\Log::info("Audit access email sent successfully to {$email} for plan {$auditPlanId}", [
                'access_url' => $accessUrl,
                'expires_at' => $expiresAt,
                'employee_name' => $employee->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access granted! Check your email for the secure access link.',
                'email_sent' => true,
                'expiresAt' => $expiresAt->toISOString()
            ]);
        } catch (\Exception $emailError) {
            \Illuminate\Support\Facades\Log::error("Failed to send audit access email to {$email}: " . $emailError->getMessage());
            \Illuminate\Support\Facades\Log::error("Email error details: " . $emailError->getTraceAsString());

            // Return error but still provide access URL as fallback
            return response()->json([
                'success' => true,
                'message' => 'Access granted, but email delivery failed. Please contact support.',
                'email_sent' => false,
                'accessUrl' => $accessUrl, // Fallback access URL
                'expiresAt' => $expiresAt->toISOString()
            ]);
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Illuminate\Support\Facades\Log::error('Employee audit access validation error: ' . json_encode($e->errors()));
        \Illuminate\Support\Facades\Log::error('Request data received: ' . json_encode($request->all()));

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 400);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Employee audit access request error: ' . $e->getMessage());
        \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while processing your request: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Employee audit data access with token
Route::get('/employee-audits/access/{token}', function ($token) {
    try {
        \Illuminate\Support\Facades\Log::info("Accessing audit data with token: {$token}");

        // Get employee info from cache
        $cacheKey = "employee_audit_access:{$token}";
        $employeeData = Cache::get($cacheKey);

        \Illuminate\Support\Facades\Log::info("Cache data: " . json_encode($employeeData));

        if (!$employeeData) {
            return response()->json([
                'success' => false,
                'message' => 'Access token expired or invalid.'
            ], 401);
        }

        // Check if token has expired
        if (Carbon::now()->isAfter($employeeData['expires_at'])) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Access token has expired.'
            ], 401);
        }

        $employeeId = $employeeData['employee_id'];

        // Get employee details
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.'
            ], 404);
        }

        // Get the specific audit plan from the token
        $auditPlanId = $employeeData['audit_plan_id'] ?? null;

        if (!$auditPlanId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid access token: missing audit plan information.'
            ], 400);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Audit plan not found or no longer accessible.'
                ], 404);
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

            \Illuminate\Support\Facades\Log::info("Audit plan data prepared", [
                'plan_name' => $auditPlan->name,
                'due_date' => $auditPlan->due_date,
                'total_assets' => $totalAssets,
                'completed_assets' => $auditedAssets,
                'progress' => $progress
            ]);

            \Illuminate\Support\Facades\Log::info("Found audit plan for employee {$employeeId}: {$auditPlan->name}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error fetching audit plan: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching audit plan details.'
            ], 500);
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
            \Illuminate\Support\Facades\Log::info("Employee {$employeeId} is an auditor for location: {$assignedLocation->name}");
        } else {
            // Check if employee has any assets assigned to them in this audit plan
            $hasAssignedAssets = \Bu\DAL\Models\Asset::where('user_id', $employeeId)
                ->whereHas('auditAssets', function ($query) use ($auditPlanId) {
                    $query->where('audit_plan_id', $auditPlanId);
                })
                ->exists();

            if ($hasAssignedAssets) {
                \Illuminate\Support\Facades\Log::info("Employee {$employeeId} has assigned assets in audit plan {$auditPlanId}");
            } else {
                \Illuminate\Support\Facades\Log::info("Employee {$employeeId} has no assigned assets in audit plan {$auditPlanId}");
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

                \Illuminate\Support\Facades\Log::info("Auditor {$employeeId} can see {$auditAssets->count()} assets in location {$assignedLocation->name}");
            } else {
                // Regular employees see only their own assigned assets
                $auditAssets = AuditAsset::where('audit_plan_id', $auditPlanId)
                    ->whereHas('asset', function ($query) use ($employee) {
                        $query->where('user_id', $employee->id);
                    })
                    ->with(['asset'])
                    ->get();

                \Illuminate\Support\Facades\Log::info("Regular employee {$employeeId} can see {$auditAssets->count()} of their assigned assets");
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
            \Illuminate\Support\Facades\Log::error("Error fetching audit assets: " . $e->getMessage());
            $auditAssets = collect([]);
        }

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email
            ],
            'selectedPlan' => $auditPlanData,
            'auditAssets' => $auditAssets,
            'role' => [
                'isAuditor' => $isAuditor,
                'assignedLocation' => $assignedLocation ? $assignedLocation->name : null,
                'canAuditAllAssets' => $isAuditor,
                'description' => $isAuditor
                    ? "You are an auditor assigned to location: {$assignedLocation->name}. You can see and audit all assets in this location."
                    : "You have assets assigned to you in this audit plan. You can see and update your own assigned assets."
            ]
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Employee audit data access error: ' . $e->getMessage());
        \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching audit data: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Employee asset status update
Route::put('/employee-audits/update-asset/{token}', function (Request $request, $token) {
    try {
        \Illuminate\Support\Facades\Log::info("Asset update request received", [
            'token' => $token,
            'request_data' => $request->all(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'assetId_type' => gettype($request->input('assetId')),
            'assetId_value' => $request->input('assetId'),
            'status_type' => gettype($request->input('status')),
            'status_value' => $request->input('status')
        ]);

        $request->validate([
            'assetId' => 'required', // Accept any type, we'll convert to integer
            'status' => 'required|string|in:欠落,返却済,廃止,保管(使用無),利用中,保管中,貸出中,故障中,利用予約,Missing,Returned,Abolished,Stored - Not in Use,In Use,In Storage,On Loan,Broken,Reserved for Use', // Validate against known asset statuses (Japanese + English)
            'notes' => 'nullable|string|max:1000', // Limit notes length
            'reassignUserId' => 'nullable|integer|exists:employees,id' // Optional user reassignment
        ]);

        \Illuminate\Support\Facades\Log::info("Validation passed, proceeding with asset update");

        // Get employee info from cache
        $cacheKey = "employee_audit_access:{$token}";
        $employeeData = Cache::get($cacheKey);

        if (!$employeeData) {
            return response()->json([
                'success' => false,
                'message' => 'Access token expired or invalid.'
            ], 401);
        }

        // Check if token has expired
        if (Carbon::now()->isAfter($employeeData['expires_at'])) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Access token has expired.'
            ], 401);
        }

        $employeeId = $employeeData['employee_id'];
        $assetId = $request->input('assetId');
        $status = $request->input('status');
        $notes = $request->input('notes');
        $reassignUserId = $request->input('reassignUserId');

        // Get employee details for tracking
        $employee = Employee::find($employeeId);

        // Find the audit asset and verify the employee has access to it
        // Employee can be either:
        // 1. An assigned auditor for this audit plan, OR
        // 2. A regular employee who needs to comply with the audit (asset assigned to them)

        // First, get the audit asset to check its audit plan
        $auditAsset = AuditAsset::where('id', $assetId)
            ->whereHas('auditPlan', function ($query) {
                $query->where('due_date', '>', Carbon::now()->toDateString())
                    ->whereIn('status', ['Planning', 'In Progress']);
            })
            ->first();

        if (!$auditAsset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found or audit plan is no longer active.'
            ], 404);
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
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to update this asset.'
            ], 403);
        }



        // Check if asset is already resolved
        if ($auditAsset->resolved) {
            return response()->json([
                'success' => false,
                'message' => 'This asset has already been resolved and cannot be updated.'
            ], 400);
        }

        // Get the main asset for comparison and updates
        $mainAsset = Asset::find($auditAsset->asset_id);

        // Check if asset location or user has changed since audit plan creation
        // Note: We only check for changes, we don't update these fields during status updates
        // The current_location and current_user fields in audit_assets are for tracking changes
        // that happen in the main assets table, not for audit status updates
        $locationChanged = $mainAsset && $mainAsset->location !== $auditAsset->original_location;
        $userChanged = $mainAsset && $mainAsset->user_id !== $auditAsset->original_user;

        // Update the audit asset - ONLY update audit-related fields, NOT location/user tracking
        // If you want to track location/user changes, you would need a separate process
        // that syncs these fields from the main assets table periodically
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
            // Check both the main asset user_id and the audit asset current_user
            if ($mainAsset->user_id || $auditAsset->current_user) {
                \Illuminate\Support\Facades\Log::warning("User assignment rejected - asset already has user", [
                    'audit_asset_id' => $auditAsset->id,
                    'asset_id' => $mainAsset->id,
                    'main_asset_user_id' => $mainAsset->user_id,
                    'audit_asset_current_user' => $auditAsset->current_user,
                    'requested_user_id' => $reassignUserId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign user to asset that already has a user assigned.'
                ], 400);
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

                \Illuminate\Support\Facades\Log::info("User assigned to audit asset", [
                    'audit_asset_id' => $auditAsset->id,
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $reassignUserId,
                    'new_user_name' => $newUserName
                ]);
            }
        }

        // Also update the main asset in the assets table
        if ($mainAsset) {
            $oldStatus = $mainAsset->status;
            $updateData = [
                'status' => $status,
                'updated_at' => Carbon::now()
            ];

            // If user was assigned, update the main asset table
            if ($userAssigned) {
                // Since we're only assigning to assets without users, 
                // original_user will be null, so we don't need to store previous_user
                $updateData['user_id'] = $reassignUserId; // Update to new user ID in main assets table
            }

            $mainAsset->update($updateData);

            \Illuminate\Support\Facades\Log::info("Main asset updated", [
                'asset_id' => $mainAsset->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'user_assigned' => $userAssigned,
                'old_user_id' => $oldUserId,
                'new_user_id' => $reassignUserId,
                'updated_by' => 'audit_system'
            ]);
        } else {
            \Illuminate\Support\Facades\Log::warning("Main asset not found for audit asset", [
                'audit_asset_id' => $auditAsset->id,
                'asset_id' => $auditAsset->asset_id
            ]);
        }

        \Illuminate\Support\Facades\Log::info("Asset status updated by employee", [
            'employee_id' => $employeeId,
            'employee_name' => $employee->name,
            'asset_id' => $assetId,
            'audit_asset_id' => $auditAsset->id,
            'old_status' => $auditAsset->getOriginal('current_status'),
            'new_status' => $status,
            'notes' => $notes,
            'audit_status' => $auditAsset->audit_status,
            'main_asset_updated' => $mainAsset ? true : false,
            'location_changed' => $locationChanged,
            'user_changed' => $userChanged,
            'user_assigned' => $userAssigned,
            'original_location' => $auditAsset->original_location,
            'current_location' => $auditAsset->current_location ?? 'Not tracked',
            'original_user' => $auditAsset->original_user,
            'current_user' => $auditAsset->current_user ?? 'Not tracked'
        ]);

        return response()->json([
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
            'main_asset_updated' => $mainAsset ? true : false,
            'user_assignment' => $userAssigned ? [
                'old_user_id' => $oldUserId,
                'new_user_id' => $reassignUserId,
                'new_user_name' => $newUserName
            ] : null,
            'changes_detected' => [
                'location' => $locationChanged,
                'user' => $userChanged
            ]
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Illuminate\Support\Facades\Log::error('Asset update validation error: ' . json_encode($e->errors()));
        \Illuminate\Support\Facades\Log::error('Request data received: ' . json_encode($request->all()));

        return response()->json([
            'success' => false,
            'message' => 'Validation failed: ' . implode(', ', array_map(function ($field, $errors) {
                return $field . ': ' . implode(', ', $errors);
            }, array_keys($e->errors()), $e->errors())),
            'errors' => $e->errors()
        ], 400);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Employee asset update error: ' . $e->getMessage());
        \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while updating the asset: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Get available audit plans for employee selection
Route::get('/employee-audits/available-plans', function () {
    try {
        \Illuminate\Support\Facades\Log::info("Fetching available audit plans for employee selection");

        // Get all active audit plans (not due yet) with progress
        $allPlans = AuditPlan::where('due_date', '>', Carbon::now()->toDateString())
            ->whereIn('status', ['Planning', 'In Progress'])
            ->orderBy('due_date', 'asc')
            ->get(['id', 'name', 'start_date', 'due_date', 'status']);

        \Illuminate\Support\Facades\Log::info("Found {$allPlans->count()} active audit plans");

        $auditPlans = $allPlans->map(function ($plan) {
            // Calculate progress for this plan
            $totalAssets = AuditAsset::where('audit_plan_id', $plan->id)->count();
            $auditedAssets = AuditAsset::where('audit_plan_id', $plan->id)
                ->where('audit_status', true)
                ->count();
            $progress = $totalAssets > 0 ? round(($auditedAssets / $totalAssets) * 100) : 0;

            \Illuminate\Support\Facades\Log::info("Plan {$plan->name} (ID: {$plan->id}): {$totalAssets} total assets, {$auditedAssets} audited, {$progress}% progress");

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

        return response()->json([
            'success' => true,
            'auditPlans' => $auditPlans
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Available audit plans error: ' . $e->getMessage());
        \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching available audit plans: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Get audit assets for a specific audit plan
Route::get('/employee-audits/plan/{token}/{planId}', function ($token, $planId) {
    try {
        \Illuminate\Support\Facades\Log::info("Accessing audit plan assets with token: {$token}, plan: {$planId}");

        // Get employee info from cache
        $cacheKey = "employee_audit_access:{$token}";
        $employeeData = Cache::get($cacheKey);

        if (!$employeeData) {
            return response()->json([
                'success' => false,
                'message' => 'Access token expired or invalid.'
            ], 401);
        }

        // Check if token has expired
        if (Carbon::now()->isAfter($employeeData['expires_at'])) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Access token has expired.'
            ], 401);
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
            return response()->json([
                'success' => false,
                'message' => 'Audit plan not found or not accessible.'
            ], 404);
        }

        // Get audit assets for this specific plan
        $auditAssets = AuditAsset::where('audit_plan_id', $planId)
            ->with(['asset'])
            ->get();

        // Calculate progress based on audit_status
        $totalAssets = $auditAssets->count();
        $auditedAssets = $auditAssets->where('audit_status', true)->count();
        $progress = $totalAssets > 0 ? round(($auditedAssets / $totalAssets) * 100) : 0;

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

        return response()->json([
            'success' => true,
            'auditPlan' => [
                'id' => $auditPlan->id,
                'name' => $auditPlan->name,
                'start_date' => $auditPlan->start_date,
                'due_date' => $auditPlan->due_date,
                'status' => $auditPlan->status
            ],
            'auditAssets' => $auditAssets,
            'progress' => [
                'total_assets' => $totalAssets,
                'audited_assets' => $auditedAssets,
                'percentage' => $progress
            ]
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Employee audit plan access error: ' . $e->getMessage());
        \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching audit plan data: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Get audit history for an employee
Route::get('/employee-audits/history/{token}', function ($token) {
    try {
        \Illuminate\Support\Facades\Log::info("Accessing audit history with token: {$token}");

        // Get employee info from cache
        $cacheKey = "employee_audit_access:{$token}";
        $employeeData = Cache::get($cacheKey);

        if (!$employeeData) {
            return response()->json([
                'success' => false,
                'message' => 'Access token expired or invalid.'
            ], 401);
        }

        // Check if token has expired
        if (Carbon::now()->isAfter($employeeData['expires_at'])) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Access token has expired.'
            ], 401);
        }

        $employeeId = $employeeData['employee_id'];

        // Get audit history - assets that have been audited
        $auditHistory = AuditAsset::whereHas('auditPlan.assignments', function ($query) use ($employeeId) {
            $query->where('auditor_id', $employeeId);
        })
            ->whereNotNull('audited_at')
            ->with(['asset', 'auditPlan'])
            ->orderBy('audited_at', 'desc')
            ->get()
            ->map(function ($auditAsset) {
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

        return response()->json([
            'success' => true,
            'auditHistory' => $auditHistory
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Employee audit history error: ' . $e->getMessage());
        \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while fetching audit history: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// REST API endpoints for the frontend
Route::get('/assets', function (Request $request) {
    $query = Asset::query();

    if ($request->has('type')) {
        $query->where('type', $request->type);
    }

    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    if ($request->has('location')) {
        $query->where('location', 'like', '%' . $request->location . '%');
    }

    if ($request->has('user')) {
        $query->where('user_id', $request->user);
    }

    return $query->get();
});

Route::get('/employees', function () {
    return Employee::all();
});

Route::get('/locations', function () {
    return Location::all();
});

Route::get('/projects', function () {
    return Project::all();
});

// Get available employees for user reassignment based on location audit assignments
Route::get('/employees', function (Request $request) {
    try {
        $locationName = $request->query('location');

        if (!$locationName) {
            return response()->json([
                'success' => false,
                'message' => 'Location parameter is required'
            ], 400);
        }

        // Decode the location name in case it's URL encoded
        $locationName = urldecode($locationName);

        \Illuminate\Support\Facades\Log::info("Fetching employees for location", [
            'raw_location' => $request->query('location'),
            'decoded_location' => $locationName
        ]);

        // Get all employees that work at this location (not just auditors)
        $employees = Employee::select('employees.id', 'employees.name', 'employees.email')
            ->where('employees.location', $locationName)
            ->orderBy('employees.name')
            ->get();

        \Illuminate\Support\Facades\Log::info("SQL query executed", [
            'location' => $locationName,
            'sql' => Employee::select('employees.id', 'employees.name', 'employees.email')
                ->where('employees.location', $locationName)
                ->orderBy('employees.name')
                ->toSql(),
            'bindings' => [
                'location' => $locationName
            ]
        ]);

        \Illuminate\Support\Facades\Log::info("Fetched employees working at location", [
            'location' => $locationName,
            'count' => $employees->count(),
            'employees' => $employees->pluck('name')->toArray()
        ]);

        return response()->json([
            'success' => true,
            'employees' => $employees
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to fetch employees working at location: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch employees: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Corrective Actions API endpoints
Route::get('/employee-audits/corrective-actions', function (Request $request) {
    try {
        $employeeId = $request->query('employee_id');
        $auditPlanId = $request->query('audit_plan_id');

        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Employee ID is required'
            ], 400);
        }

        if (!$auditPlanId) {
            return response()->json([
                'success' => false,
                'message' => 'Audit Plan ID is required'
            ], 400);
        }

        // Determine employee role for this audit plan
        $isAuditor = false;
        $assignedLocation = null;

        // Check if employee is assigned as an auditor for this plan
        $auditAssignment = \Bu\DAL\Models\AuditAssignment::where('audit_plan_id', $auditPlanId)
            ->where('auditor_id', $employeeId)
            ->first();

        if ($auditAssignment) {
            $isAuditor = true;
            $assignedLocation = $auditAssignment->location;
        }

        // Both auditors and regular employees see only corrective actions assigned to them
        // No role restrictions - everyone sees only their assigned actions
        $actions = \Bu\DAL\Models\CorrectiveAction::with(['auditAsset.asset'])
            ->where('assigned_to', $employeeId)
            ->whereHas('auditAsset', function ($query) use ($auditPlanId) {
                $query->where('audit_plan_id', $auditPlanId);
            })
            ->get();

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

        return response()->json([
            'success' => true,
            'actions' => $actions,
            'role' => [
                'isAuditor' => $isAuditor,
                'assignedLocation' => $assignedLocation ? $assignedLocation->name : null,
                'canSeeAllActions' => false, // No one can see all actions anymore
                'description' => "You can see corrective actions assigned to you for this audit plan."
            ]
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to fetch corrective actions: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch corrective actions: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

Route::put('/employee-audits/update-action-status', function (Request $request) {
    try {
        $request->validate([
            'action_id' => 'required|integer',
            'status' => 'required|string|in:pending,in_progress,completed',
            'notes' => 'nullable|string',
            'employee_id' => 'required|integer'
        ]);

        $actionId = $request->input('action_id');
        $status = $request->input('status');
        $notes = $request->input('notes');
        $employeeId = $request->input('employee_id');

        // Find the corrective action
        $action = \Bu\DAL\Models\CorrectiveAction::find($actionId);

        if (!$action) {
            return response()->json([
                'success' => false,
                'message' => 'Corrective action not found'
            ], 404);
        }

        // Verify the employee is assigned to this action
        if ($action->assigned_to != $employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this corrective action'
            ], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'Action status updated successfully'
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to update action status: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to update action status: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Get audit asset details with status history
Route::get('/employee-audits/audit-asset/{auditAssetId}', function (Request $request, $auditAssetId) {
    try {
        $auditAsset = \Bu\DAL\Models\AuditAsset::with(['asset', 'correctiveActions'])
            ->find($auditAssetId);

        if (!$auditAsset) {
            return response()->json([
                'success' => false,
                'message' => 'Audit asset not found'
            ], 404);
        }

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

        return response()->json([
            'success' => true,
            'audit_asset' => $data
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to fetch audit asset: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch audit asset: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Bulk update corrective actions status
Route::put('/employee-audits/bulk-update-actions', function (Request $request) {
    try {
        $request->validate([
            'action_ids' => 'required|array',
            'action_ids.*' => 'integer',
            'status' => 'required|string|in:pending,in_progress,completed',
            'notes' => 'nullable|string',
        ]);

        $actionIds = $request->input('action_ids');
        $status = $request->input('status');
        $notes = $request->input('notes');

        $results = \Bu\DAL\Models\CorrectiveAction::bulkUpdateStatus($actionIds, $status, $notes);

        return response()->json([
            'success' => true,
            'message' => 'Bulk update completed',
            'results' => $results
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to bulk update actions: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to bulk update actions: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Test route for email functionality - remove after testing
Route::get('/test-email', function () {
    try {
        \Illuminate\Support\Facades\Mail::raw('Test email from Asset Management System - SMTP is working!', function ($message) {
            $message->to('test@example.com')
                ->subject('SMTP Test - Asset Management System')
                ->from(env('MAIL_FROM_ADDRESS', 'noreply@yourcompany.com'), env('MAIL_FROM_NAME', 'Asset Management System'));
        });

        \Illuminate\Support\Facades\Log::info('Test email sent successfully');
        return response()->json([
            'success' => true,
            'message' => 'Test email sent successfully! Check Mailtrap inbox.',
            'config' => [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name')
            ]
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Test email failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Email failed: ' . $e->getMessage(),
            'error_details' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Test route for audit notification emails - remove after testing
Route::get('/test-audit-notification', function () {
    try {
        // Get a sample employee and audit plan for testing
        $employee = \Bu\DAL\Models\Employee::first();
        $auditPlan = \Bu\DAL\Models\AuditPlan::first();

        if (!$employee || !$auditPlan) {
            return response()->json([
                'success' => false,
                'message' => 'No employees or audit plans found for testing'
            ], 404);
        }

        // Send test audit notification email
        \Illuminate\Support\Facades\Mail::to($employee->email)->send(new \App\Mail\AuditPlanNotificationEmail(
            $auditPlan,
            $employee,
            collect([]), // Empty assets for testing
            $auditPlan->due_date
        ));

        \Illuminate\Support\Facades\Log::info('Test audit notification email sent successfully');
        return response()->json([
            'success' => true,
            'message' => 'Test audit notification email sent successfully! Check your Gmail inbox.',
            'details' => [
                'employee_email' => $employee->email,
                'audit_plan_name' => $auditPlan->name,
                'due_date' => $auditPlan->due_date
            ]
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Test audit notification email failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Test audit notification email failed: ' . $e->getMessage(),
            'error_details' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Test route for the complete notification service - remove after testing
Route::get('/test-notification-service', function () {
    try {
        // Get sample data
        $employees = \Bu\DAL\Models\Employee::take(2)->get();
        $locations = \Bu\DAL\Models\Location::take(2)->get();

        if ($employees->isEmpty() || $locations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No employees or locations found for testing'
            ], 404);
        }

        // Show how many employees have assets in these locations
        $locationNames = $locations->pluck('name')->toArray();
        $employeesWithAssets = \Bu\DAL\Models\Employee::whereHas('assignedAssets', function ($query) use ($locationNames) {
            $query->whereIn('location', $locationNames);
        })->get();

        // Create a test audit plan
        $testAuditPlan = \Bu\DAL\Models\AuditPlan::create([
            'name' => 'Test Notification Service - ' . date('Y-m-d H:i:s'),
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'Planning',
            'created_by' => 1,
            'description' => 'Test audit plan for notification service testing'
        ]);

        // Test the notification service
        $notificationService = new \App\Services\AuditNotificationService();
        $notificationsSent = $notificationService->sendInitialNotifications(
            $testAuditPlan,
            $employees->pluck('id')->toArray(),
            $locations->pluck('id')->toArray()
        );

        // Clean up test data
        $testAuditPlan->delete();

        \Illuminate\Support\Facades\Log::info('Test notification service completed successfully');
        return response()->json([
            'success' => true,
            'message' => 'Test notification service completed successfully!',
            'details' => [
                'test_audit_plan_created' => true,
                'notifications_sent' => $notificationsSent,
                'assigned_auditors' => $employees->count(),
                'employees_with_assets' => $employees->count(),
                'total_employees_notified' => $notificationsSent,
                'locations_tested' => $locations->count(),
                'location_names' => $locationNames,
                'test_data_cleaned_up' => true
            ]
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Test notification service failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Test notification service failed: ' . $e->getMessage(),
            'error_details' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Test route for corrective action notifications - remove after testing
Route::get('/test-corrective-action-notification', function () {
    try {
        // Get sample data
        $correctiveAction = App\Models\CorrectiveAction::with(['auditAsset.asset', 'auditPlan'])->first();

        if (!$correctiveAction) {
            return response()->json([
                'success' => false,
                'message' => 'No corrective actions found for testing. Please create some corrective actions first.'
            ], 404);
        }

        // Test the corrective action notification service
        $notificationService = new App\Services\CorrectiveActionNotificationService();
        $result = $notificationService->sendCorrectiveActionNotification($correctiveAction);

        \Illuminate\Support\Facades\Log::info('Test corrective action notification completed', $result);
        return response()->json([
            'success' => true,
            'message' => 'Test corrective action notification completed!',
            'details' => $result
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Test corrective action notification failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Test corrective action notification failed: ' . $e->getMessage(),
            'error_details' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Test route for overdue corrective action reminders - remove after testing
Route::get('/test-corrective-action-reminders', function () {
    try {
        // Test the overdue reminders
        $notificationService = new \App\Services\CorrectiveActionNotificationService();
        $result = $notificationService->sendOverdueReminders();

        \Illuminate\Support\Facades\Log::info('Test corrective action reminders completed', $result);
        return response()->json([
            'success' => true,
            'message' => 'Test corrective action reminders completed!',
            'details' => $result
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Test corrective action reminders failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Test corrective action reminders failed: ' . $e->getMessage(),
            'error_details' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Send reminders for specific corrective actions
Route::post('/send-corrective-action-reminders', function () {
    try {
        $request = request();
        $actionIds = $request->input('action_ids', []);

        if (empty($actionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No action IDs provided'
            ], 400);
        }

        // Get the corrective actions
        $actions = \Bu\DAL\Models\CorrectiveAction::whereIn('id', $actionIds)
            ->with(['auditAsset.asset', 'auditPlan'])
            ->get();

        if ($actions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No corrective actions found'
            ], 404);
        }

        // Send reminders using the notification service
        $notificationService = new \App\Services\CorrectiveActionNotificationService();
        $result = $notificationService->sendBulkNotifications($actionIds);

        \Illuminate\Support\Facades\Log::info('Bulk corrective action reminders sent', [
            'action_ids' => $actionIds,
            'result' => $result
        ]);

        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Test POST route
Route::post('/test-post', function (Request $request) {
    return response()->json(['message' => 'POST test works', 'data' => $request->all()]);
});

// Send scheduled reminders for pending and in-progress corrective actions
Route::post('/send-scheduled-corrective-action-reminders', function () {
    try {
        // Send scheduled reminders using the notification service
        $notificationService = new \App\Services\CorrectiveActionNotificationService();
        $result = $notificationService->sendScheduledReminders();

        \Illuminate\Support\Facades\Log::info('Scheduled corrective action reminders sent', [
            'result' => $result
        ]);

        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});
