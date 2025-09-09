<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\CorrectiveAction;
use Bu\Server\Models\AuditAsset;
use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\CorrectiveActionAssignment;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Services\CorrectiveActionNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CorrectiveActionMutations
{
    /**
     * Create a new corrective action.
     */
    public function create($rootValue, array $args)
    {
        $input = $args['action'];
        
        // Validate that the audit asset exists and belongs to the audit plan
        $auditAsset = AuditAsset::find($input['audit_asset_id']);
        if (!$auditAsset) {
            throw new \Exception('Audit asset not found');
        }
        
        $auditPlan = AuditPlan::find($auditAsset->audit_plan_id);
        if (!$auditPlan) {
            throw new \Exception('Audit plan not found');
        }
        
        // Find the audit assignment for this location
        $auditAssignment = AuditAssignment::where('audit_plan_id', $auditAsset->audit_plan_id)
            ->whereHas('location', function ($query) use ($auditAsset) {
                $query->where('name', $auditAsset->original_location);
            })
            ->first();
        
        if (!$auditAssignment) {
            throw new \Exception('No audit assignment found for location: ' . $auditAsset->original_location);
        }
        
        // Create the corrective action
        $correctiveAction = CorrectiveAction::create([
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
        
        // Send notifications to assigned employees
        try {
            $notificationService = new CorrectiveActionNotificationService();
            $notificationResult = $notificationService->sendCorrectiveActionNotification($correctiveAction);
            
            Log::info('Corrective action notification sent after creation', [
                'corrective_action_id' => $correctiveAction->id,
                'notification_result' => $notificationResult
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send corrective action notification after creation', [
                'corrective_action_id' => $correctiveAction->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the creation if notification fails
        }
        
        return $correctiveAction->load(['auditAsset.asset', 'auditPlan', 'assignment.auditAssignment.auditor']);
    }
    
    /**
     * Update an existing corrective action.
     */
    public function update($rootValue, array $args)
    {
        $correctiveAction = CorrectiveAction::find($args['id']);
        if (!$correctiveAction) {
            throw new \Exception('Corrective action not found');
        }
        
        $input = $args['action'];
        
        // Update the corrective action
        $correctiveAction->update([
            'issue' => $input['issue'],
            'action' => $input['action'],
            'assigned_to' => $input['assigned_to'] ?? null,
            'priority' => $input['priority'] ?? 'medium',
            'due_date' => $input['due_date'],
            'notes' => $input['notes'] ?? null,
        ]);
        
        // Send notifications if assignment changed
        if (isset($input['assigned_to']) && $input['assigned_to'] !== $correctiveAction->getOriginal('assigned_to')) {
            try {
                $notificationService = new CorrectiveActionNotificationService();
                $notificationResult = $notificationService->sendCorrectiveActionNotification($correctiveAction);
                
                Log::info('Corrective action notification sent after assignment update', [
                    'corrective_action_id' => $correctiveAction->id,
                    'notification_result' => $notificationResult
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send corrective action notification after assignment update', [
                    'corrective_action_id' => $correctiveAction->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the update if notification fails
            }
        }
        
        return $correctiveAction->load(['auditAsset.asset', 'auditPlan']);
    }
    
    /**
     * Update the status of a corrective action.
     */
    public function updateStatus($rootValue, array $args)
    {
        $correctiveAction = CorrectiveAction::find($args['id']);
        if (!$correctiveAction) {
            throw new \Exception('Corrective action not found');
        }
        
        $status = $args['status'];
        $notes = $args['notes'] ?? null;

        // If status is completed, use markAsCompleted to handle cascading updates
        if ($status === 'completed') {
            $resolutionStatus = $correctiveAction->getResolutionStatus();
            $success = $correctiveAction->markAsCompleted($notes, $resolutionStatus);
            if (!$success) {
                throw new \Exception('Failed to mark corrective action as completed');
            }

            // Also mark the assignment as completed
            if ($assignment = $correctiveAction->assignment) {
                $assignment->markAsCompleted();
                if ($notes) {
                    $assignment->updateProgressNotes($notes);
                }
            }
        } else {
            // For other statuses, just update the corrective action
            $updateData = [
                'status' => $status,
                'notes' => $notes ? $correctiveAction->notes . "\n\nStatus Update: " . $notes : $correctiveAction->notes,
            ];
            
            // If status is overdue, check if due date has passed
            if ($status !== 'completed' && $correctiveAction->due_date < Carbon::now()) {
                $updateData['status'] = 'overdue';
            }
            
            $correctiveAction->update($updateData);

            // Update assignment status to match
            if ($assignment = $correctiveAction->assignment) {
                $assignment->update(['status' => $status]);
                if ($notes) {
                    $assignment->updateProgressNotes($notes);
                }
            }
        }
        
        // Send notifications for status change
        try {
            $notificationService = new CorrectiveActionNotificationService();
            $notificationResult = $notificationService->sendCorrectiveActionNotification($correctiveAction);
            
            Log::info('Corrective action notification sent after status update', [
                'corrective_action_id' => $correctiveAction->id,
                'new_status' => $status,
                'notification_result' => $notificationResult
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send corrective action notification after status update', [
                'corrective_action_id' => $correctiveAction->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the status update if notification fails
        }
        
        // Refresh relationships to get updated data
        $correctiveAction->refresh();
        return $correctiveAction->load(['auditAsset.asset', 'auditPlan', 'assignment']);
    }
    
    /**
     * Delete a corrective action.
     */
    public function delete($rootValue, array $args)
    {
        $correctiveAction = CorrectiveAction::find($args['id']);
        if (!$correctiveAction) {
            throw new \Exception('Corrective action not found');
        }
        
        $correctiveAction->delete();
        
        return true;
    }
}
