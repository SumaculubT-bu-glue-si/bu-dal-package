<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\CorrectiveAction;
use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\CorrectiveActionAssignment;
use Bu\DAL\Models\AuditAssignment;
use Bu\DAL\Database\Repositories\CorrectiveActionRepository;
use Bu\DAL\Database\Repositories\AuditAssetRepository;
use Bu\DAL\Database\Repositories\AuditPlanRepository;
use Bu\DAL\Database\Repositories\AuditAssignmentRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CorrectiveActionMutations
{
    public function __construct(
        private CorrectiveActionRepository $correctiveActionRepository,
        private AuditAssetRepository $auditAssetRepository,
        private AuditPlanRepository $auditPlanRepository,
        private AuditAssignmentRepository $auditAssignmentRepository
    ) {}

    /**
     * Create a new corrective action.
     */
    public function create($rootValue, array $args)
    {
        $input = $args['action'];

        // Validate that the audit asset exists and belongs to the audit plan
        $auditAsset = $this->auditAssetRepository->find($input['audit_asset_id']);
        if (!$auditAsset) {
            throw new \Exception('Audit asset not found');
        }

        $auditPlan = $this->auditPlanRepository->find($auditAsset->audit_plan_id);
        if (!$auditPlan) {
            throw new \Exception('Audit plan not found');
        }

        // Find the audit assignment for this location
        $auditAssignment = $this->auditAssignmentRepository->whereFirst('audit_plan_id', '=', $auditAsset->audit_plan_id);
        if (!$auditAssignment) {
            throw new \Exception('No audit assignment found for location: ' . $auditAsset->original_location);
        }

        // Create the corrective action
        $correctiveAction = $this->correctiveActionRepository->create([
            'audit_asset_id' => $input['audit_asset_id'],
            'audit_plan_id' => $auditAsset->audit_plan_id,
            'issue' => $input['issue'],
            'action' => $input['action'],
            'assigned_to' => $input['assigned_to'] ?? null,
            'priority' => $input['priority'] ?? 'medium',
            'status' => 'pending',
            'due_date' => $input['due_date'],
            'notes' => $input['notes'] ?? null,
        ]);

        // Create the corrective action assignment
        $assignment = CorrectiveActionAssignment::create([
            'corrective_action_id' => $correctiveAction->id,
            'audit_assignment_id' => $auditAssignment->id,
            'assigned_to_employee_id' => $auditAssignment->auditor_id, // Default to the auditor
            'status' => 'pending',
        ]);

        return $correctiveAction->load(['auditAsset.asset', 'auditPlan', 'assignment.auditAssignment.auditor']);
    }

    /**
     * Update an existing corrective action.
     */
    public function update($rootValue, array $args)
    {
        $correctiveAction = $this->correctiveActionRepository->find($args['id']);
        if (!$correctiveAction) {
            throw new \Exception('Corrective action not found');
        }

        $input = $args['action'];

        // Update the corrective action
        $this->correctiveActionRepository->update($correctiveAction->id, [
            'issue' => $input['issue'],
            'action' => $input['action'],
            'assigned_to' => $input['assigned_to'] ?? null,
            'priority' => $input['priority'] ?? 'medium',
            'due_date' => $input['due_date'],
            'notes' => $input['notes'] ?? null,
        ]);

        return $correctiveAction->load(['auditAsset.asset', 'auditPlan']);
    }

    /**
     * Update the status of a corrective action.
     */
    public function updateStatus($rootValue, array $args)
    {
        $correctiveAction = $this->correctiveActionRepository->find($args['id']);
        if (!$correctiveAction) {
            throw new \Exception('Corrective action not found');
        }

        $status = $args['status'];
        $notes = $args['notes'] ?? null;

        $updateData = [
            'status' => $status,
            'notes' => $notes ? $correctiveAction->notes . "\n\nStatus Update: " . $notes : $correctiveAction->notes,
        ];

        // If status is completed, set completed_date
        if ($status === 'completed') {
            $updateData['completed_date'] = Carbon::now();
        } else {
            $updateData['completed_date'] = null;
        }

        // If status is overdue, check if due date has passed
        if ($status !== 'completed' && $correctiveAction->due_date < Carbon::now()) {
            $updateData['status'] = 'overdue';
        }

        $this->correctiveActionRepository->update($correctiveAction->id, $updateData);

        return $correctiveAction->load(['auditAsset.asset', 'auditPlan']);
    }

    /**
     * Delete a corrective action.
     */
    public function delete($rootValue, array $args)
    {
        $correctiveAction = $this->correctiveActionRepository->find($args['id']);
        if (!$correctiveAction) {
            throw new \Exception('Corrective action not found');
        }

        $this->correctiveActionRepository->delete($correctiveAction->id);

        return true;
    }
}