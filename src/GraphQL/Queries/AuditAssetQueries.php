<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\AuditAsset;
use Bu\DAL\Database\Repositories\AuditAssetRepository;

class AuditAssetQueries
{
    public function __construct(
        private AuditAssetRepository $auditAssetRepository
    ) {}

    /**
     * Find audit assets for a specific audit plan
     */
    public function auditAssets($rootValue, array $args)
    {
        $query = AuditAsset::query();

        if (isset($args['audit_plan_id'])) {
            $query->where('audit_plan_id', $args['audit_plan_id']);
        }

        if (isset($args['current_status'])) {
            $query->where('current_status', $args['current_status']);
        }

        $perPage = $args['first'] ?? 50;
        $page = request()->get('page', 1);

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }
}
