<?php

namespace Bu\Server\GraphQL\Resolvers;

use Bu\Server\Models\Employee;

class EmployeeFieldResolver
{
    /**
     * Resolve the assigned_at field for an employee when loaded via
     * ServiceSubscription->employees() pivot.
     */
    public function assignedAt(Employee $employee)
    {
        return $employee->pivot->created_at ?? null;
    }
}
