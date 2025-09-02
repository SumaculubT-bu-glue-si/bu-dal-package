<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\CorrectiveActionAssignment;
use Illuminate\Database\Eloquent\Collection;

class CorrectiveActionAssignmentRepository extends BaseRepository
{
    protected string $modelClass = CorrectiveActionAssignment::class;

    /**
     * Get corrective action assignments by corrective action.
     */
    public function getByCorrectiveAction(int $correctiveActionId): Collection
    {
        return $this->newQuery()->where('corrective_action_id', $correctiveActionId)->get();
    }

    /**
     * Get corrective action assignments by audit assignment.
     */
    public function getByAuditAssignment(int $auditAssignmentId): Collection
    {
        return $this->newQuery()->where('audit_assignment_id', $auditAssignmentId)->get();
    }

    /**
     * Get corrective action assignments by employee.
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return $this->newQuery()->where('assigned_to_employee_id', $employeeId)->get();
    }

    /**
     * Get corrective action assignments by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->newQuery()->where('status', $status)->get();
    }

    /**
     * Get completed assignments.
     */
    public function getCompleted(): Collection
    {
        return $this->getByStatus('completed');
    }

    /**
     * Get in-progress assignments.
     */
    public function getInProgress(): Collection
    {
        return $this->getByStatus('in_progress');
    }

    /**
     * Get overdue assignments.
     */
    public function getOverdue(): Collection
    {
        return $this->getByStatus('overdue');
    }

    /**
     * Get corrective action assignment statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $completed = $this->getCompleted()->count();
        $inProgress = $this->getInProgress()->count();
        $overdue = $this->getOverdue()->count();

        $byStatus = $this->newQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'by_status' => $byStatus,
        ];
    }
}
