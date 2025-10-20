<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\ServiceSubscription;
use Bu\Server\Models\License;
use Bu\Server\Models\Employee;

final class AssignEmployeeToSubscriptionResolver
{
    public function __invoke($_, array $args)
    {
        $subscription = ServiceSubscription::find($args['subscription_id']);
        if (! $subscription) {
            return ['success' => false, 'message' => 'Subscription not found'];
        }

        $employee = Employee::where('employee_id', $args['employee_id'])->first();
        if (! $employee) {
            return ['success' => false, 'message' => 'Employee not found'];
        }

        if ($subscription->license_type === 'per-license') {
            $license = License::find($args['license_id'] ?? null);
            if (! $license) {
                return ['success' => false, 'message' => 'License not found'];
            }

            // licenses.assigned_employee_id references employees.id (numeric FK)
            $license->assigned_employee_id = $employee->employee_id;
            $license->used = true;
            $license->save();
        } else {
            // per-seat → attach using employees.employee_id since pivot FK references employee_id
            $subscription->employees()->syncWithoutDetaching([$employee->employee_id]);
        }

        return ['success' => true, 'message' => 'Employee assigned successfully'];
    }
}