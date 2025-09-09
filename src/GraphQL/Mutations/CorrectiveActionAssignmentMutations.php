<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\CorrectiveActionAssignment;
use Bu\Server\Models\CorrectiveAction;
use Bu\Server\Models\Employee;
use Bu\Server\Services\CorrectiveActionNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CorrectiveActionAssignmentMutations
{
    /**
     * Assign a corrective action to an employee.
     */
    public function assign($rootValue, array $args)
    {
        $correctiveActionId = $args['corrective_action_id'];
        $assignedToEmployeeId = $args['assigned_to_employee_id'];
        $notes = $args['notes'] ?? null;
        
        // Find the corrective action
        $correctiveAction = CorrectiveAction::find($correctiveActionId);
        if (!$correctiveAction) {
            throw new \Exception('Corrective action not found');
        }
        
        // Find the employee
        $employee = Employee::find($assignedToEmployeeId);
        if (!$employee) {
            throw new \Exception('Employee not found');
        }
        
        // Find or create the assignment
        $assignment = CorrectiveActionAssignment::where('corrective_action_id', $correctiveActionId)->first();
        
        if ($assignment) {
            // Update existing assignment
            $assignment->update([
                'assigned_to_employee_id' => $assignedToEmployeeId,
                'progress_notes' => $notes,
            ]);
        } else {
            // Create new assignment
            $assignment = CorrectiveActionAssignment::create([
                'corrective_action_id' => $correctiveActionId,
                'audit_assignment_id' => $correctiveAction->audit_plan_id, // This will need to be updated based on your logic
                'assigned_to_employee_id' => $assignedToEmployeeId,
                'status' => 'pending',
                'progress_notes' => $notes,
            ]);
        }
        
        // Send notifications to newly assigned employee
        try {
            $notificationService = new CorrectiveActionNotificationService();
            $notificationResult = $notificationService->sendCorrectiveActionNotification($correctiveAction);
            
            Log::info('Corrective action notification sent after assignment', [
                'corrective_action_id' => $correctiveActionId,
                'assigned_employee_id' => $assignedToEmployeeId,
                'notification_result' => $notificationResult
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send corrective action notification after assignment', [
                'corrective_action_id' => $correctiveActionId,
                'assigned_employee_id' => $assignedToEmployeeId,
                'error' => $e->getMessage()
            ]);
            // Don't fail the assignment if notification fails
        }
        
        return $assignment->load(['correctiveAction.auditAsset.asset', 'assignedToEmployee', 'auditAssignment.auditor']);
    }
    
    /**
     * Update the status of a corrective action assignment.
     */
    public function updateStatus($rootValue, array $args)
    {
        $assignmentId = $args['id'];
        $status = $args['status'];
        $progressNotes = $args['progress_notes'] ?? null;
        
        // Find the assignment
        $assignment = CorrectiveActionAssignment::find($assignmentId);
        if (!$assignment) {
            throw new \Exception('Corrective action assignment not found');
        }
        
        $updateData = [
            'status' => $status,
        ];
        
        // Update timestamps based on status
        if ($status === 'in_progress' && !$assignment->started_at) {
            $updateData['started_at'] = Carbon::now();
        } elseif ($status === 'completed') {
            $updateData['completed_at'] = Carbon::now();
        }
        
        // Update progress notes if provided
        if ($progressNotes) {
            $currentNotes = $assignment->progress_notes ?: '';
            $updateData['progress_notes'] = $currentNotes . "\n\n" . Carbon::now()->format('Y-m-d H:i:s') . " - " . $progressNotes;
        }
        
        $assignment->update($updateData);
        
        // Send completion notification if status changed to completed
        if ($status === 'completed' && $assignment->getOriginal('status') !== 'completed') {
            try {
                $notificationService = new CorrectiveActionNotificationService();
                $notificationResult = $notificationService->sendCorrectiveActionNotification($assignment->correctiveAction);
                
                Log::info('Corrective action completion notification sent', [
                    'assignment_id' => $assignmentId,
                    'corrective_action_id' => $assignment->corrective_action_id,
                    'notification_result' => $notificationResult
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send corrective action completion notification', [
                    'assignment_id' => $assignmentId,
                    'corrective_action_id' => $assignment->corrective_action_id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the status update if notification fails
            }
        }
        
        return $assignment->load(['correctiveAction.auditAsset.asset', 'assignedToEmployee', 'auditAssignment.auditor']);
    }
}
