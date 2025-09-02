<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\CorrectiveAction;
use Illuminate\Database\Eloquent\Collection;

class CorrectiveActionRepository extends BaseRepository
{
    protected string $modelClass = CorrectiveAction::class;

    /**
     * Get corrective actions by audit plan.
     */
    public function getByAuditPlan(int $auditPlanId): Collection
    {
        return $this->newQuery()->where('audit_plan_id', $auditPlanId)->get();
    }

    /**
     * Get corrective actions by audit asset.
     */
    public function getByAuditAsset(int $auditAssetId): Collection
    {
        return $this->newQuery()->where('audit_asset_id', $auditAssetId)->get();
    }

    /**
     * Get corrective actions by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->newQuery()->where('status', $status)->get();
    }

    /**
     * Get corrective actions by priority.
     */
    public function getByPriority(string $priority): Collection
    {
        return $this->newQuery()->where('priority', $priority)->get();
    }

    /**
     * Get overdue corrective actions.
     */
    public function getOverdue(): Collection
    {
        return $this->newQuery()
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->get();
    }

    /**
     * Get completed corrective actions.
     */
    public function getCompleted(): Collection
    {
        return $this->getByStatus('completed');
    }

    /**
     * Get pending corrective actions.
     */
    public function getPending(): Collection
    {
        return $this->getByStatus('pending');
    }

    /**
     * Get corrective action statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $completed = $this->getCompleted()->count();
        $pending = $this->getPending()->count();
        $overdue = $this->getOverdue()->count();

        $byStatus = $this->newQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byPriority = $this->newQuery()
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'overdue' => $overdue,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
        ];
    }
}
