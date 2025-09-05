<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\AuditAssignment;
use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Models\Asset;
use Bu\DAL\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Bu\DAL\Services\AuditNotificationService;

class CreateAuditPlan
{
    public function __invoke($rootValue, array $args)
    {
        try {
            // Enhanced validation with detailed error messages
            $this->validateInput($args);

            DB::beginTransaction();

            // Create the audit plan
            $auditPlan = AuditPlan::create([
                'name' => $args['name'],
                'start_date' => $args['start_date'],
                'due_date' => $args['due_date'],
                'description' => $args['description'] ?? null,
                'status' => 'Planning',
                'created_by' => Auth::id(),
            ]);

            // Create audit assignments for each auditor
            foreach ($args['auditors'] as $auditorId) {
                AuditAssignment::create([
                    'audit_plan_id' => $auditPlan->id,
                    'auditor_id' => $auditorId,
                    'status' => 'Assigned',
                    'assigned_at' => now(),
                ]);
            }

            // Create audit assets for each location
            foreach ($args['locations'] as $locationId) {
                $assets = Asset::where('location_id', $locationId)->get();

                foreach ($assets as $asset) {
                    AuditAsset::create([
                        'audit_plan_id' => $auditPlan->id,
                        'asset_id' => $asset->id,
                        'current_status' => $asset->status,
                        'current_location' => $asset->location->name ?? 'Unknown',
                        'current_user' => $asset->employee->name ?? 'Unassigned',
                        'status' => 'Pending',
                    ]);
                }
            }

            // Log the creation
            AuditLog::create([
                'audit_plan_id' => $auditPlan->id,
                'action' => 'created',
                'details' => "Audit plan '{$auditPlan->name}' created",
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            // Send notifications
            try {
                $notificationService = app(AuditNotificationService::class);
                // Note: sendAuditPlanNotifications method needs to be implemented in AuditNotificationService
                // $notificationService->sendAuditPlanNotifications($auditPlan);
            } catch (\Exception $e) {
                Log::error('Failed to send audit plan notifications: ' . $e->getMessage());
            }

            return $auditPlan->load(['auditAssignments.auditor', 'auditAssets.asset']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create audit plan: ' . $e->getMessage());
            throw new \Exception('Failed to create audit plan: ' . $e->getMessage());
        }
    }

    private function validateInput(array $args): void
    {
        $errors = [];

        if (empty($args['name'])) {
            $errors[] = 'Audit plan name is required';
        }

        if (empty($args['start_date'])) {
            $errors[] = 'Start date is required';
        }

        if (empty($args['due_date'])) {
            $errors[] = 'Due date is required';
        }

        if (!empty($args['start_date']) && !empty($args['due_date'])) {
            if (strtotime($args['start_date']) >= strtotime($args['due_date'])) {
                $errors[] = 'Due date must be after start date';
            }
        }

        if (empty($args['auditors']) || !is_array($args['auditors'])) {
            $errors[] = 'At least one auditor must be assigned';
        }

        if (empty($args['locations']) || !is_array($args['locations'])) {
            $errors[] = 'At least one location must be selected';
        }

        if (!empty($errors)) {
            throw new \Exception('Validation failed: ' . implode(', ', $errors));
        }
    }
}