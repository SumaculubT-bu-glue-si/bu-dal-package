<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\AuditPlan;
use Illuminate\Database\Eloquent\Collection;

class AuditPlanRepository extends BaseRepository
{
    protected string $modelClass = AuditPlan::class;

    /**
     * Get audit plans by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->newQuery()->where('status', $status)->get();
    }

    /**
     * Get audit plans by creator.
     */
    public function getByCreator(int $createdBy): Collection
    {
        return $this->newQuery()->where('created_by', $createdBy)->get();
    }

    /**
     * Get active audit plans.
     */
    public function getActive(): Collection
    {
        return $this->newQuery()
            ->where('status', 'In Progress')
            ->where('due_date', '>', now())
            ->get();
    }

    /**
     * Get overdue audit plans.
     */
    public function getOverdue(): Collection
    {
        return $this->newQuery()
            ->where('due_date', '<', now())
            ->where('status', '!=', 'Completed')
            ->get();
    }

    /**
     * Get audit plans with assignments.
     */
    public function getWithAssignments(int $auditPlanId): ?AuditPlan
    {
        return $this->newQuery()
            ->with('assignments')
            ->find($auditPlanId);
    }

    /**
     * Get audit plans with audit assets.
     */
    public function getWithAuditAssets(int $auditPlanId): ?AuditPlan
    {
        return $this->newQuery()
            ->with('auditAssets')
            ->find($auditPlanId);
    }

    /**
     * Get audit plans with corrective actions.
     */
    public function getWithCorrectiveActions(int $auditPlanId): ?AuditPlan
    {
        return $this->newQuery()
            ->with('correctiveActions')
            ->find($auditPlanId);
    }

    /**
     * Get audit plan statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $active = $this->getActive()->count();
        $overdue = $this->getOverdue()->count();
        $completed = $this->getByStatus('Completed')->count();

        $byStatus = $this->newQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'overdue' => $overdue,
            'completed' => $completed,
            'by_status' => $byStatus,
        ];
    }
}
