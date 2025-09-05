<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Models\AuditLog;
use Bu\DAL\Database\Repositories\AuditAssetRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UpdateAuditAsset
{
    public function __construct(
        private AuditAssetRepository $auditAssetRepository
    ) {}

    public function __invoke($rootValue, array $args)
    {
        try {
            Log::info('UpdateAuditAsset called with args:', $args);

            $auditAsset = $this->auditAssetRepository->find($args['id']);
            if (!$auditAsset) {
                throw new \Exception("Audit asset not found");
            }

            // Store old values for logging
            $oldValues = $auditAsset->only(['current_status', 'auditor_notes', 'resolved', 'current_location', 'current_user']);

            // Update the audit asset
            $updateData = array_filter($args, function ($key) {
                return in_array($key, ['current_status', 'auditor_notes', 'resolved', 'current_location', 'current_user']);
            }, ARRAY_FILTER_USE_KEY);

            Log::info('UpdateAuditAsset filtered data:', $updateData);

            // If current_location or current_user are not provided, get them from the current asset
            if (!isset($updateData['current_location'])) {
                $updateData['current_location'] = $auditAsset->asset->location;
            }

            if (!isset($updateData['current_user'])) {
                // Get the current employee name from the user_id relationship
                $updateData['current_user'] = $auditAsset->asset->employee ? $auditAsset->asset->employee->name : null;
            }

            // Add audit timestamp and auditor if status is being updated
            if (isset($args['current_status']) && $args['current_status'] !== 'Pending') {
                $updateData['audited_at'] = now();
                // Get authenticated user name or use a fallback
                $updateData['audited_by'] = Auth::user()?->name ?? 'System';
            }

            Log::info('UpdateAuditAsset updating with data:', $updateData);
            $this->auditAssetRepository->update($auditAsset->id, $updateData);
            Log::info('UpdateAuditAsset update completed');

            // If the asset is being resolved, update the main assets table
            if (isset($updateData['resolved']) && $updateData['resolved'] === true) {
                Log::info('Asset is being resolved, updating main assets table');
                try {
                    $auditAsset->updateMainAssetDirectly();
                    Log::info('Main assets table updated successfully');
                } catch (\Exception $mainAssetError) {
                    Log::error('Failed to update main assets table', [
                        'error' => $mainAssetError->getMessage(),
                        'audit_asset_id' => $auditAsset->id
                    ]);
                    // Don't fail the main operation, but log the error
                }
            }

            // Log the update - use authenticated user ID or a fallback
            $performedBy = Auth::user()?->id ?? 'system';

            try {
                AuditLog::log(
                    $auditAsset->audit_plan_id,
                    'Asset Status Updated',
                    $performedBy,
                    $auditAsset->asset_id,
                    $oldValues,
                    $auditAsset->only(['current_status', 'auditor_notes', 'resolved', 'current_location', 'current_user']),
                    "Asset {$auditAsset->asset->asset_id} status updated to {$auditAsset->current_status}"
                );
            } catch (\Exception $logError) {
                // Log the error but don't fail the main operation
                Log::warning('Failed to create audit log entry', [
                    'error' => $logError->getMessage(),
                    'audit_asset_id' => $auditAsset->id,
                    'performed_by' => $performedBy
                ]);
            }

            return $auditAsset->fresh()->load('asset');
        } catch (\Exception $e) {
            Log::error('Error updating audit asset', [
                'error' => $e->getMessage(),
                'audit_asset_id' => $args['id'] ?? 'unknown',
                'args' => $args
            ]);

            throw $e;
        }
    }
}
