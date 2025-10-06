<?php

namespace Bu\Server\Database\Repositories;

use Bu\Server\Models\Employee;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository extends BaseRepository
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    /**
     * Find employee by employee_id.
     */
    public function findByEmployeeId(string $employeeId): ?Employee
    {
        return $this->model->where('employee_id', $employeeId)->first();
    }

    /**
     * Find employee by email.
     */
    public function findByEmail(string $email): ?Employee
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Upsert employee by employee_id.
     */
    public function upsertByEmployeeId(array $data): Employee
    {
        return $this->model->updateOrCreate(
            ['employee_id' => $data['employee_id']],
            $data
        );
    }

    /**
     * Get employees by location.
     */
    public function getByLocation(string $location): Collection
    {
        return $this->model->where('location', $location)->get();
    }

    /**
     * Get employees with assets in specific locations.
     */
    public function getEmployeesWithAssetsInLocations(array $locationNames): Collection
    {
        return $this->model->whereHas('assignedAssets', function ($query) use ($locationNames) {
            $query->whereIn('location', $locationNames);
        })->get();
    }

    /**
     * Search employees by name.
     */
    public function searchByName(string $name): Collection
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    /**
     * Get employees by project.
     */
    public function getByProject(string $project): Collection
    {
        return $this->model->whereJsonContains('projects', $project)->get();
    }

    /**
     * Bulk upsert employees.
     */
    public function bulkUpsert(array $employees): Collection
    {
        $results = new Collection();

        foreach ($employees as $employeeData) {
            $employee = $this->upsertByEmployeeId($employeeData);
            $results->push($employee);
        }

        return $results;
    }
}