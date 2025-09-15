<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\Employee;
use Bu\DAL\Database\Repositories\EmployeeRepository;

class EmployeeQueries
{
    /**
     * Get all employees with optional filtering.
     */
    public function employees($rootValue, array $args)
    {
        $query = Employee::query();

        // Apply filters if provided
        if (isset($args['name']) && $args['name']) {
            $query->where('name', 'like', '%' . $args['name'] . '%');
        }

        if (isset($args['email']) && $args['email']) {
            $query->where('email', 'like', '%' . $args['email'] . '%');
        }

        if (isset($args['location']) && $args['location']) {
            $query->where('location', 'like', '%' . $args['location'] . '%');
        }

        if (isset($args['org_unit_path']) && $args['org_unit_path']) {
            $query->where('org_unit_path', $args['org_unit_path']);
        }

        // Order by name by default
        $query->orderBy('name');

        return $query->get();
    }

    /**
     * Get a single employee by ID.
     */
    public function employee($rootValue, array $args)
    {
        $id = $args['id'];
        
        return Employee::find($id);
    }

    /**
     * Get available organizational units
     */
    public function orgUnits($rootValue, array $args)
    {
        $orgUnits = Employee::getAvailableOrgUnits();
        
        $result = [];
        foreach ($orgUnits as $path => $name) {
            $result[] = [
                'path' => $path,
                'name' => $name,
            ];
        }
        
        return $result;
    }
}
