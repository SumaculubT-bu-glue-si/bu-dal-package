<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class UpdateAuditAssignment
{
    public function __invoke($rootValue, array $args)
    {
        $assignment = AuditAssignment::findOrFail($args['id']);
        
        // Store old values for logging
        $oldValues = $assignment->only(['status', 'notes']);
        
        // Update the assignment
        $updateData = array_filter($args, function ($key) {
            return in_array($key, ['status', 'notes']);
        }, ARRAY_FILTER_USE_KEY);
        
        $assignment->update($updateData);
        
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
