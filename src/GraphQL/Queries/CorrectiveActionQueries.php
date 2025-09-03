<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\CorrectiveAction;
use Bu\DAL\Database\Repositories\CorrectiveActionRepository;

class CorrectiveActionQueries
{
    public function __construct(
        private CorrectiveActionRepository $correctiveActionRepository
    ) {}

    /**
     * Find a single corrective action by ID
     */
    public function correctiveAction($rootValue, array $args)
    {
        return CorrectiveAction::find($args['id']);
    }

    /**
     * Find corrective actions for a specific audit plan
     */
    public function correctiveActions($rootValue, array $args)
    {
        $query = CorrectiveAction::query();

        if (isset($args['audit_plan_id'])) {
            $query->where('audit_plan_id', $args['audit_plan_id']);
        }

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }

        if (isset($args['priority'])) {
            $query->where('priority', $args['priority']);
        }

        if (isset($args['assigned_to'])) {
            $query->where('assigned_to', 'like', '%' . $args['assigned_to'] . '%');
        }

        $perPage = $args['first'] ?? 50;
        $page = request()->get('page', 1);

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }
}
