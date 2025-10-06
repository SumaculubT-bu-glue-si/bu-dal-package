<?php

namespace Bu\Server\Database\Repositories;

use Bu\Server\Models\CorrectiveAction;
use Illuminate\Database\Eloquent\Collection;

class CorrectiveActionRepository extends BaseRepository
{
    public function __construct(CorrectiveAction $model)
    {
        parent::__construct($model);
    }

    /**
     * Get corrective actions by audit plan.
     */
    public function getByAuditPlan(int $auditPlanId): Collection
    {
        return $this->model->where('audit_plan_id', $auditPlanId)->get();
    }

    /**
     * Get corrective actions by audit asset.
     */
    public function getByAuditAsset(int $auditAssetId): Collection
    {
        return $this->model->where('audit_asset_id', $auditAssetId)->get();
    }

    /**
     * Get corrective actions by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get corrective actions by priority.
     */
    public function getByPriority(string $priority): Collection
    {
        return $this->model->where('priority', $priority)->get();
    }

    /**
     * Get corrective actions by assignee.
     */
    public function getByAssignee(string $assignedTo): Collection
    {
        return $this->model->where('assigned_to', 'like', "%{$assignedTo}%")->get();
    }

    /**
     * Get pending corrective actions.
     */
    public function getPending(): Collection
    {
        return $this->model->where('status', 'pending')->get();
    }

    /**
     * Get in-progress corrective actions.
     */
    public function getInProgress(): Collection
    {
        return $this->model->where('status', 'in_progress')->get();
    }

    /**
     * Get completed corrective actions.
     */
    public function getCompleted(): Collection
    {
        return $this->model->where('status', 'completed')->get();
    }

    /**
     * Get overdue corrective actions.
     */
    public function getOverdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    /**
     * Get corrective action with details.
     */
    public function getWithDetails(int $id): ?CorrectiveAction
    {
        return $this->model->with(['auditAsset.asset', 'auditPlan', 'assignments'])
            ->find($id);
    }

    /**
     * Mark corrective action as completed.
     */
    public function markAsCompleted(int $id, ?string $notes = null, ?string $resolutionStatus = null): bool
    {
        $action = $this->find($id);
        if (!$action) {
            return false;
        }

        return $action->markAsCompleted($notes, $resolutionStatus);
    }

    /**
     * Bulk update corrective actions status.
     */
    public function bulkUpdateStatus(array $actionIds, string $status, ?string $notes = null): array
    {
        return CorrectiveAction::bulkUpdateStatus($actionIds, $status, $notes);
    }
}