<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\Employee;
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
            'employee_id' => $input['employee_id'],
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
}
