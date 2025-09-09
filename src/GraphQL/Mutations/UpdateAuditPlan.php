<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\AuditPlan;
use Bu\Server\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class UpdateAuditPlan
{
    public function __invoke($rootValue, array $args)
    {
        $auditPlan = AuditPlan::findOrFail($args['id']);
        
        // Store old values for logging
        $oldValues = $auditPlan->only(['name', 'start_date', 'due_date', 'status', 'description']);
        
        // Update the audit plan
        $auditPlan->update(array_filter($args, function ($key) {
            return in_array($key, ['name', 'start_date', 'due_date', 'status', 'description']);
        }, ARRAY_FILTER_USE_KEY));
        
        // Log the update
        AuditLog::log(
            $auditPlan->id,
            'Updated',
            Auth::id(),
            null,
            $oldValues,
            $auditPlan->only(['name', 'start_date', 'due_date', 'status', 'description']),
            "Audit plan '{$auditPlan->name}' updated"
        );
        
        return $auditPlan->fresh();
    }
}
