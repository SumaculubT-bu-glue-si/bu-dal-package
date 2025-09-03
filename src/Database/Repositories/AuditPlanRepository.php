<?php

namespace Bu\DAL\Database\Repositories;

use Bu\DAL\Models\AuditPlan;
use Illuminate\Database\Eloquent\Collection;

class AuditPlanRepository extends BaseRepository
{
    public function __construct(AuditPlan $model)
    {
        parent::__construct($model);
    }

    /**
     * Get audit plans by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get audit plans by creator.
     */
    public function getByCreator(int $createdBy): Collection
    {
        return $this->model->where('created_by', $createdBy)->get();
    }

    /**
     * Get active audit plans.
     */
    public function getActive(): Collection
    {
        return $this->model->where('status', 'In Progress')
            ->where('due_date', '>', now())
            ->get();
    }

    /**
     * Get overdue audit plans.
     */
    public function getOverdue(): Collection
    {
        return $this->model->where('due_date', '<', now())
            ->where('status', '!=', 'Completed')
            ->get();
    }

    /**
     * Get audit plans due soon.
     */
    public function getDueSoon(int $days = 7): Collection
    {
        return $this->model->where('due_date', '<=', now()->addDays($days))
            ->where('due_date', '>', now())
            ->where('status', '!=', 'Completed')
            ->get();
    }

    /**
     * Get audit plan with assignments and assets.
     */
    public function getWithDetails(int $id): ?AuditPlan
    {
        return $this->model->with(['assignments.location', 'assignments.auditor', 'auditAssets.asset'])
            ->find($id);
    }

    /**
     * Get audit plan summary.
     */
    public function getSummary(int $id): ?array
    {
        $auditPlan = $this->find($id);
        if (!$auditPlan) {
            return null;
        }

        return $auditPlan->getAuditSummary();
    }
}
