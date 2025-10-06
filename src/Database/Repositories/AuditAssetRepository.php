<?php

namespace Bu\Server\Database\Repositories;

use Bu\Server\Models\AuditAsset;
use Illuminate\Database\Eloquent\Collection;

class AuditAssetRepository extends BaseRepository
{
    public function __construct(AuditAsset $model)
    {
        parent::__construct($model);
    }

    /**
     * Get audit assets by audit plan.
     */
    public function getByAuditPlan(int $auditPlanId): Collection
    {
        return $this->model->where('audit_plan_id', $auditPlanId)->get();
    }

    /**
     * Get audit assets by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('current_status', $status)->get();
    }

    /**
     * Get audited assets.
     */
    public function getAudited(): Collection
    {
        return $this->model->where('audit_status', true)->get();
    }

    /**
     * Get pending audit assets.
     */
    public function getPending(): Collection
    {
        return $this->model->where('audit_status', false)->get();
    }

    /**
     * Get resolved audit assets.
     */
    public function getResolved(): Collection
    {
        return $this->model->where('resolved', true)->get();
    }

    /**
     * Get unresolved audit assets.
     */
    public function getUnresolved(): Collection
    {
        return $this->model->where('resolved', false)->get();
    }

    /**
     * Get audit assets with issues.
     */
    public function getWithIssues(): Collection
    {
        return $this->model->whereIn('current_status', ['故障中', '廃止'])->get();
    }

    /**
     * Get audit assets by auditor.
     */
    public function getByAuditor(string $auditor): Collection
    {
        return $this->model->where('audited_by', $auditor)->get();
    }

    /**
     * Get audit asset with details.
     */
    public function getWithDetails(int $id): ?AuditAsset
    {
        return $this->model->with(['auditPlan', 'asset', 'correctiveActions'])
            ->find($id);
    }

    /**
     * Mark asset as audited.
     */
    public function markAsAudited(int $id, string $auditor, string $status, ?string $notes = null): bool
    {
        $auditAsset = $this->find($id);
        if (!$auditAsset) {
            return false;
        }

        $auditAsset->markAsAudited($auditor, $status, $notes);
        return true;
    }
}