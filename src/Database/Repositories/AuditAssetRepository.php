<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\AuditAsset;
use Illuminate\Database\Eloquent\Collection;

class AuditAssetRepository extends BaseRepository
{
    protected string $modelClass = AuditAsset::class;

    /**
     * Get audit assets by audit plan.
     */
    public function getByAuditPlan(int $auditPlanId): Collection
    {
        return $this->newQuery()->where('audit_plan_id', $auditPlanId)->get();
    }

    /**
     * Get audit assets by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->newQuery()->where('current_status', $status)->get();
    }

    /**
     * Get audited assets.
     */
    public function getAudited(): Collection
    {
        return $this->newQuery()->where('audit_status', true)->get();
    }

    /**
     * Get resolved assets.
     */
    public function getResolved(): Collection
    {
        return $this->newQuery()->where('resolved', true)->get();
    }

    /**
     * Get audit assets with corrective actions.
     */
    public function getWithCorrectiveActions(int $auditAssetId): ?AuditAsset
    {
        return $this->newQuery()
            ->with('correctiveActions')
            ->find($auditAssetId);
    }

    /**
     * Get audit asset statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $audited = $this->getAudited()->count();
        $resolved = $this->getResolved()->count();

        $byStatus = $this->newQuery()
            ->selectRaw('current_status, COUNT(*) as count')
            ->groupBy('current_status')
            ->pluck('count', 'current_status')
            ->toArray();

        return [
            'total' => $total,
            'audited' => $audited,
            'resolved' => $resolved,
            'by_status' => $byStatus,
        ];
    }
}
