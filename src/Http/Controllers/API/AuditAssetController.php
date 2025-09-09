<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Http\Controllers\Controller;
use Bu\Server\Models\AuditAsset;
use Bu\Server\Models\Asset;
use Bu\Server\Http\Requests\AuditAssetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AuditAssetController extends Controller
{
    /**
     * Display a listing of audit assets.
     */
    public function index(Request $request): JsonResponse
    {
        $assets = AuditAsset::with(['asset', 'auditPlan'])
            ->when($request->plan_id, function ($query, $planId) {
                return $query->where('audit_plan_id', $planId);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('current_status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($assets);
    }

    /**
     * Store a newly created audit asset.
     */
    public function store(AuditAssetRequest $request): JsonResponse
    {
        $auditAsset = AuditAsset::create($request->validated());
        return response()->json($auditAsset->load(['asset', 'auditPlan']), 201);
    }

    /**
     * Display the specified audit asset.
     */
    public function show(AuditAsset $auditAsset): JsonResponse
    {
        return response()->json($auditAsset->load(['asset', 'auditPlan']));
    }

    /**
     * Update the specified audit asset.
     */
    public function update(AuditAssetRequest $request, AuditAsset $auditAsset): JsonResponse
    {
        $auditAsset->update($request->validated());
        return response()->json($auditAsset->load(['asset', 'auditPlan']));
    }

    /**
     * Remove the specified audit asset.
     */
    public function destroy(AuditAsset $auditAsset): JsonResponse
    {
        $auditAsset->delete();
        return response()->json(null, 204);
    }

    /**
     * Update asset status with token verification.
     */
    public function updateWithToken(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'assetId' => 'required',
            'status' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'reassignUserId' => 'nullable|exists:employees,id'
        ]);

        $employeeData = Cache::get("employee_audit_access:{$token}");
        if (!$employeeData || Carbon::now()->isAfter($employeeData['expires_at'])) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        $auditAsset = AuditAsset::findOrFail($request->input('assetId'));
        
        // Verify access
        $hasAccess = $this->verifyAccess($auditAsset, $employeeData['employee_id']);
        if (!$hasAccess) {
            return response()->json(['message' => 'You do not have access to update this asset'], 403);
        }

        // Update audit asset
        $auditAsset->update([
            'current_status' => $request->input('status'),
            'auditor_notes' => $request->input('notes'),
            'audited_at' => now(),
            'audit_status' => true,
            'audited_by' => $employeeData['employee_id']
        ]);

        // Update main asset if needed
        if ($request->input('reassignUserId')) {
            Asset::where('id', $auditAsset->asset_id)->update([
                'user_id' => $request->input('reassignUserId'),
                'status' => $request->input('status')
            ]);
        }

        return response()->json($auditAsset->load('asset'));
    }

    /**
     * Get audit assets for an employee.
     */
    public function getEmployeeAssets(string $token): JsonResponse
    {
        $employeeData = Cache::get("employee_audit_access:{$token}");
        if (!$employeeData || Carbon::now()->isAfter($employeeData['expires_at'])) {
            return response()->json(['message' => 'Invalid or expired token'], 401);
        }

        $assets = AuditAsset::whereHas('asset', function ($query) use ($employeeData) {
            $query->where('user_id', $employeeData['employee_id']);
        })->with(['asset', 'auditPlan'])->get();

        return response()->json($assets);
    }

    private function verifyAccess(AuditAsset $auditAsset, int $employeeId): bool
    {
        // Check if employee is an auditor
        $isAuditor = $auditAsset->auditPlan->assignments()
            ->where('auditor_id', $employeeId)
            ->exists();

        // If not auditor, check if they own the asset
        if (!$isAuditor) {
            return $auditAsset->asset->user_id === $employeeId;
        }

        return true;
    }
}
