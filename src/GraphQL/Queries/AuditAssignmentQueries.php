<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\AuditAssignment;
use Bu\DAL\Database\Repositories\AuditAssignmentRepository;

class AuditAssignmentQueries
{
    public function __construct(
        private AuditAssignmentRepository $auditAssignmentRepository
    ) {}

    /**
     * Find audit assignments for a specific audit plan
     */
    public function auditAssignments($rootValue, array $args)
    {
        $query = AuditAssignment::query();

        if (isset($args['audit_plan_id'])) {
            $query->where('audit_plan_id', $args['audit_plan_id']);
        }

        if (isset($args['auditor_id'])) {
            $query->where('auditor_id', $args['auditor_id']);
        }

        $perPage = $args['first'] ?? 20;
        $page = request()->get('page', 1);

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }
}
