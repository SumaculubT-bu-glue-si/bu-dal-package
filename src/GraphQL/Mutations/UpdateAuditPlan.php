<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\AuditPlan;
use Bu\DAL\Models\AuditLog;
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

        // Log the changes
        $changes = [];
        foreach ($oldValues as $key => $oldValue) {
            if (isset($args[$key]) && $args[$key] != $oldValue) {
                $changes[] = "{$key}: '{$oldValue}' → '{$args[$key]}'";
            }
        }

        if (!empty($changes)) {
            AuditLog::create([
                'audit_plan_id' => $auditPlan->id,
                'action' => 'updated',
                'details' => 'Updated: ' . implode(', ', $changes),
                'user_id' => Auth::id(),
            ]);
        }

        return $auditPlan->fresh();
    }
}
