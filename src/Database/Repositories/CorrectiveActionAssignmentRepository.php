<?php

namespace Bu\DAL\Database\Repositories;

use Bu\DAL\Models\CorrectiveActionAssignment;
use Illuminate\Database\Eloquent\Collection;

class CorrectiveActionAssignmentRepository extends BaseRepository
{
    public function __construct(CorrectiveActionAssignment $model)
    {
        parent::__construct($model);
    }

    /**
     * Get assignments by corrective action.
     */
    public function getByCorrectiveAction(int $correctiveActionId): Collection
    {
        return $this->model->where('corrective_action_id', $correctiveActionId)->get();
    }

    /**
     * Get assignments by audit assignment.
     */
    public function getByAuditAssignment(int $auditAssignmentId): Collection
    {
        return $this->model->where('audit_assignment_id', $auditAssignmentId)->get();
    }

    /**
     * Get assignments by employee.
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model->where('assigned_to_employee_id', $employeeId)->get();
    }

    /**
     * Get assignments by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get pending assignments.
     */
    public function getPending(): Collection
    {
        return $this->model->where('status', 'pending')->get();
    }

    /**
     * Get in-progress assignments.
     */
    public function getInProgress(): Collection
    {
        return $this->model->where('status', 'in_progress')->get();
    }

    /**
     * Get completed assignments.
     */
    public function getCompleted(): Collection
    {
        return $this->model->where('status', 'completed')->get();
    }

    /**
     * Get overdue assignments.
     */
    public function getOverdue(): Collection
    {
        return $this->model->where('status', 'overdue')->get();
    }

    /**
     * Get assignment with details.
     */
    public function getWithDetails(int $id): ?CorrectiveActionAssignment
    {
        return $this->model->with(['correctiveAction', 'auditAssignment', 'assignedToEmployee'])
            ->find($id);
    }

    /**
     * Mark assignment as started.
     */
    public function markAsStarted(int $id): bool
    {
        $assignment = $this->find($id);
        if (!$assignment) {
            return false;
        }

        $assignment->markAsStarted();
        return true;
    }

    /**
     * Mark assignment as completed.
     */
    public function markAsCompleted(int $id): bool
    {
        $assignment = $this->find($id);
        if (!$assignment) {
            return false;
        }

        $assignment->markAsCompleted();
        return true;
    }

    /**
     * Update progress notes.
     */
    public function updateProgressNotes(int $id, string $notes): bool
    {
        $assignment = $this->find($id);
        if (!$assignment) {
            return false;
        }

        $assignment->updateProgressNotes($notes);
        return true;
    }
}
