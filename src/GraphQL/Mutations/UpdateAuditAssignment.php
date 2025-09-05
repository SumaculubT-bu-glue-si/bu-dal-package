<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\AuditAssignment;
use Bu\DAL\Models\AuditLog;
use Bu\DAL\Database\Repositories\AuditAssignmentRepository;
use Illuminate\Support\Facades\Auth;

class UpdateAuditAssignment
{
    public function __construct(
        private AuditAssignmentRepository $auditAssignmentRepository
    ) {}

    public function __invoke($rootValue, array $args)
    {
        $assignment = $this->auditAssignmentRepository->find($args['id']);
        if (!$assignment) {
            throw new \Exception("Audit assignment not found");
        }

        // Store old values for logging
        $oldValues = $assignment->only(['status', 'notes']);

        // Update the assignment
        $updateData = array_filter($args, function ($key) {
            return in_array($key, ['status', 'notes']);
        }, ARRAY_FILTER_USE_KEY);

        $this->auditAssignmentRepository->update($assignment->id, $updateData);

        // Log the update
        AuditLog::log(
            $assignment->audit_plan_id,
            'Assignment Updated',
            Auth::id(),
            null,
            $oldValues,
            $assignment->only(['status', 'notes']),
            "Audit assignment for location {$assignment->location->name} updated to {$assignment->status}"
        );

        return $assignment->fresh()->load(['location', 'auditor']);
    }
}
