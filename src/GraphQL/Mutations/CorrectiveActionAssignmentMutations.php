<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\CorrectiveActionAssignment;
use Bu\DAL\Models\CorrectiveAction;
use Bu\DAL\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CorrectiveActionAssignmentMutations
{
    /**
     * Assign a corrective action to an employee
     */
    public function assign($rootValue, array $args)
    {
        try {
            DB::beginTransaction();

            // Validate corrective action exists
            $correctiveAction = CorrectiveAction::findOrFail($args['corrective_action_id']);

            // Validate employee exists
            $employee = Employee::findOrFail($args['assigned_to_employee_id']);

            // Check if assignment already exists
            $existingAssignment = CorrectiveActionAssignment::where([
                'corrective_action_id' => $args['corrective_action_id'],
                'assigned_to_employee_id' => $args['assigned_to_employee_id']
            ])->first();

            if ($existingAssignment) {
                throw new \Exception('This corrective action is already assigned to this employee');
            }

            // Create the assignment
            $assignment = CorrectiveActionAssignment::create([
                'corrective_action_id' => $args['corrective_action_id'],
                'assigned_to_employee_id' => $args['assigned_to_employee_id'],
                'assigned_by' => Auth::id(),
                'status' => 'pending',
                'notes' => $args['notes'] ?? null,
                'assigned_at' => now(),
            ]);

            DB::commit();

            Log::info("Corrective action {$correctiveAction->id} assigned to employee {$employee->id}");

            return $assignment->load(['correctiveAction', 'assignedToEmployee']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign corrective action: ' . $e->getMessage());
            throw new \Exception('Failed to assign corrective action: ' . $e->getMessage());
        }
    }

    /**
     * Update the status of a corrective action assignment
     */
    public function updateStatus($rootValue, array $args)
    {
        try {
            $assignment = CorrectiveActionAssignment::findOrFail($args['id']);

            $oldStatus = $assignment->status;
            $assignment->update([
                'status' => $args['status'],
                'progress_notes' => $args['progress_notes'] ?? $assignment->progress_notes,
                'updated_at' => now(),
            ]);

            Log::info("Corrective action assignment {$assignment->id} status updated from {$oldStatus} to {$args['status']}");

            return $assignment->fresh()->load(['correctiveAction', 'assignedToEmployee']);
        } catch (\Exception $e) {
            Log::error('Failed to update corrective action assignment status: ' . $e->getMessage());
            throw new \Exception('Failed to update assignment status: ' . $e->getMessage());
        }
    }

    /**
     * Delete a corrective action assignment
     */
    public function delete($rootValue, array $args)
    {
        try {
            $assignment = CorrectiveActionAssignment::findOrFail($args['id']);
            $assignment->delete();

            Log::info("Corrective action assignment {$assignment->id} deleted");

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete corrective action assignment: ' . $e->getMessage());
            throw new \Exception('Failed to delete assignment: ' . $e->getMessage());
        }
    }
}
