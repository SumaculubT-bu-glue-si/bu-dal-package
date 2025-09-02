<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\AuditAssignment;
use Illuminate\Database\Eloquent\Collection;

class AuditAssignmentRepository extends BaseRepository
{
    protected string $modelClass = AuditAssignment::class;

    /**
     * Get audit assignments by audit plan.
     */
    public function getByAuditPlan(int $auditPlanId): Collection
    {
        return $this->newQuery()->where('audit_plan_id', $auditPlanId)->get();
    }

    /**
     * Get audit assignments by auditor.
     */
    public function getByAuditor(int $auditorId): Collection
    {
        return $this->newQuery()->where('auditor_id', $auditorId)->get();
    }

    /**
     * Get audit assignments by location.
     */
    public function getByLocation(int $locationId): Collection
    {
        return $this->newQuery()->where('location_id', $locationId)->get();
    }

    /**
     * Get audit assignments by status.
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
        return $this->getByStatus('Completed');
    }

    /**
     * Get in-progress assignments.
     */
    public function getInProgress(): Collection
    {
        return $this->getByStatus('In Progress');
    }

    /**
     * Get audit assignment statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $completed = $this->getCompleted()->count();
        $inProgress = $this->getInProgress()->count();

        $byStatus = $this->newQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'by_status' => $byStatus,
        ];
    }
}
