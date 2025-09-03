<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\Employee;
use Bu\DAL\Database\Repositories\EmployeeRepository;

class EmployeeQueries
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {}

    /**
     * Find a single employee by ID
     */
    public function employee($rootValue, array $args)
    {
        return Employee::find($args['id']);
    }

    /**
     * List multiple employees with optional filtering
     */
    public function employees($rootValue, array $args)
    {
        $query = Employee::query();

        if (isset($args['name'])) {
            $query->where('name', 'like', '%' . $args['name'] . '%');
        }

        $perPage = $args['first'] ?? 20;
        $page = request()->get('page', 1);

        return $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
    }
}
