<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Database\Repositories\AuditPlanRepository;

class AuditPlanQueries
{
    public function __construct(
        private AuditPlanRepository $auditPlanRepository
    ) {}

    /**
     * Find a single audit plan by ID
     */
    public function auditPlan($rootValue, array $args)
    {
        return AuditPlan::find($args['id']);
    }

    /**
     * List multiple audit plans with optional filtering
     */
    public function auditPlans($rootValue, array $args)
    {
        $query = AuditPlan::query();

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }

        if (isset($args['created_by'])) {
            $query->where('created_by', $args['created_by']);
        }

        $perPage = $args['first'] ?? 20;
        $page = request()->get('page', 1);

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }
}
