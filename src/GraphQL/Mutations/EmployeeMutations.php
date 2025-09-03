<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\Employee;
use Bu\DAL\Database\Repositories\EmployeeRepository;
use Bu\DAL\Database\DatabaseManager;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class EmployeeMutations
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private DatabaseManager $databaseManager
    ) {}

    /**
     * Create a new employee
     */
    public function create($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['employee'];

            // Enforce unique email if provided
            if (!empty($input['email'])) {
                $exists = Employee::where('email', $input['email'])->exists();
                if ($exists) {
                    throw new \GraphQL\Error\Error('An employee with this email already exists.');
                }
            }

            return $this->employeeRepository->create([
                'employee_id' => $input['employee_id'],
                'name' => $input['name'],
                'email' => $input['email'] ?? null,
                'location' => $input['location'] ?? null,
                'projects' => $input['projects'] ?? [],
            ]);
        });
    }

    /**
     * Update an existing employee
     */
    public function update($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['employee'];
            $id = $args['id'];

            $employee = $this->employeeRepository->findOrFail($id);

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
        });
    }

    /**
     * Delete an employee
     */
    public function delete($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $id = $args['id'];
            $this->employeeRepository->delete($id);
            return true;
        });
    }

    /**
     * Upsert an employee (create if doesn't exist, update if it does)
     */
    public function upsertEmployee($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['employee'];
            return $this->employeeRepository->upsertByEmployeeId($input);
        });
    }

    /**
     * Bulk upsert employees
     */
    public function bulkUpsertEmployees($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $employees = $args['employees'];
            $results = $this->employeeRepository->bulkUpsert($employees);
            return $results->all();
        });
    }
}
