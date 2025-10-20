<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\Employee;
use Bu\Server\Models\ServiceSubscription;
use Bu\Server\Models\License;
use Illuminate\Support\Facades\DB;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class EmployeeMutations
{
    /**
     * Create a new employee
     */
    public function create($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['employee'];

        // Enforce unique email if provided
        if (!empty($input['email'])) {
            $exists = Employee::where('email', $input['email'])->exists();
            if ($exists) {
                throw new \GraphQL\Error\Error('An employee with this email already exists.');
            }
        }

        $employee = Employee::create([
            'employee_id' => $input['employee_id'],
            'name' => $input['name'],
            'email' => $input['email'] ?? null,
            'location' => $input['location'] ?? null,
            'projects' => $input['projects'] ?? [],
        ]);

        return $employee;
    }

    /**
     * Update an existing employee
     */
    public function update($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['employee'];
        $id = $args['id'];

        $employee = Employee::findOrFail($id);

        // Enforce unique email if provided (excluding current record)
        if (!empty($input['email'])) {
            $exists = Employee::where('email', $input['email'])
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                throw new \GraphQL\Error\Error('An employee with this email already exists.');
            }
        }

        $employee->update([
            'name' => $input['name'],
            'email' => $input['email'] ?? null,
            'location' => $input['location'] ?? null,
            'projects' => $input['projects'] ?? [],
        ]);

        return $employee;
    }

    /**
     * Delete an employee
     */
    public function delete($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $id = $args['id'];

        $employee = Employee::findOrFail($id);
        $employee->delete();

        return true;
    }

    /**
     * Upsert an employee (create if doesn't exist, update if it does)
     */
    public function upsertEmployee($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['employee'];

        $employee = Employee::updateOrCreate(
            ['employee_id' => $input['employee_id']],
            [
                'name' => $input['name'],
                'email' => $input['email'] ?? null,
                'location' => $input['location'] ?? null,
                'projects' => $input['projects'] ?? [],
            ]
        );

        return $employee;
    }

    /**
     * Bulk upsert employees
     */
    public function bulkUpsertEmployees($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $employees = $args['employees'];
        $result = [];

        foreach ($employees as $employeeData) {
            $employee = Employee::updateOrCreate(
                ['employee_id' => $employeeData['employee_id']],
                [
                    'name' => $employeeData['name'],
                    'email' => $employeeData['email'] ?? null,
                    'location' => $employeeData['location'] ?? null,
                    'projects' => $employeeData['projects'] ?? [],
                ]
            );

            $result[] = $employee;
        }

        return $result;
    }

    /**
     * Assign an employee either to a license or a service subscription,
     * depending on the subscription's license_type.
     */
    public function assignEmployee($root, array $args, GraphQLContext $context)
    {
        $subscriptionId = $args['subscription_id'] ?? null;
        $employeeId = $args['employee_id'] ?? null;
        $licenseId = $args['license_id'] ?? null;

        if (!$subscriptionId || !$employeeId) {
            return [
                'success' => false,
                'message' => 'Missing subscription_id or employee_id.'
            ];
        }

        try {
            return DB::transaction(function () use ($subscriptionId, $employeeId, $licenseId) {
                $subscription = ServiceSubscription::findOrFail($subscriptionId);
                $employee = Employee::where('employee_id', $employeeId)->firstOrFail();

                if ($subscription->pricing_type === 'per-license') {
                    if (!$licenseId) {
                        throw new \Exception('License ID is required for per-license subscriptions.');
                    }

                    $license = License::where('service_subscription_id', $subscriptionId)
                        ->where('id', $licenseId)
                        ->firstOrFail();

                    // licenses.assigned_employee_id references employees.id (numeric FK)
                    $license->assigned_employee_id = $employee->employee_id;
                    $license->used = true;
                    $license->save();

                    return [
                        'success' => true,
                        'message' => "Employee {$employee->name} assigned to license {$license->id}."
                    ];
                } else {
                    // For per-seat subscriptions, connect the employee directly to the subscription.
                    // The pivot column stores employees.employee_id, not employees.id.
                    $subscription->employees()->syncWithoutDetaching([$employee->employee_id]);

                    return [
                        'success' => true,
                        'message' => "Employee {$employee->name} assigned to subscription {$subscription->id}."
                    ];
                }
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unassign an employee from a per-seat service subscription.
     */
    public function unassignEmployee($root, array $args, GraphQLContext $context)
    {
        $subscriptionId = $args['subscription_id'] ?? null;
        $employeeId = $args['employee_id'] ?? null;

        if (!$subscriptionId || !$employeeId) {
            return [
                'success' => false,
                'message' => 'Missing subscription_id or employee_id.'
            ];
        }

        try {
            return DB::transaction(function () use ($subscriptionId, $employeeId) {
                $subscription = ServiceSubscription::findOrFail($subscriptionId);
                $employee = Employee::where('employee_id', $employeeId)->firstOrFail();

                if ($subscription->pricing_type !== 'per-seat') {
                    return [
                        'success' => false,
                        'message' => 'Unassign is only applicable to per-seat subscriptions.'
                    ];
                }

                $subscription->employees()->detach($employee->employee_id);

                return [
                    'success' => true,
                    'message' => "Employee {$employee->name} unassigned from subscription {$subscription->id}."
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update pivot created_at (assigned date) for per-seat subscription assignment
     */
    public function updatePerSeatAssignedDate($root, array $args)
    {
        $subscriptionId = $args['subscription_id'] ?? null;
        $employeeId = $args['employee_id'] ?? null;
        $assignedDate = $args['assigned_date'] ?? null;

        if (!$subscriptionId || !$employeeId || !$assignedDate) {
            return ['success' => false, 'message' => 'Missing required fields.'];
        }

        try {
            return DB::transaction(function () use ($subscriptionId, $employeeId, $assignedDate) {
                $subscription = ServiceSubscription::findOrFail($subscriptionId);

                if ($subscription->pricing_type !== 'per-seat') {
                    return ['success' => false, 'message' => 'Only per-seat subscriptions supported.'];
                }

                // Ensure relation exists
                $existing = $subscription->employees()
                    ->where('employees.employee_id', $employeeId)
                    ->exists();

                if (!$existing) {
                    return ['success' => false, 'message' => 'Employee not assigned to this subscription.'];
                }

                // Update pivot created_at to assigned_date
                $subscription->employees()->updateExistingPivot($employeeId, [
                    'created_at' => $assignedDate,
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'message' => 'Assigned date updated.'];
            });
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
