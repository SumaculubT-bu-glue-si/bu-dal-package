<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\Employee;
use YourCompany\GraphQLDAL\Models\Asset;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository extends BaseRepository
{
    protected string $modelClass = Employee::class;

    /**
     * Find employee by employee_id.
     */
    public function findByEmployeeId(string $employeeId): ?Employee
    {
        return $this->newQuery()->where('employee_id', $employeeId)->first();
    }

    /**
     * Find employees by employee_ids.
     */
    public function findByEmployeeIds(array $employeeIds): Collection
    {
        return $this->newQuery()->whereIn('employee_id', $employeeIds)->get();
    }

    /**
     * Upsert employee by employee_id.
     */
    public function upsertByEmployeeId(array $data): Employee
    {
        return $this->dbManager->transaction(function () use ($data) {
            $employeeId = $data['employee_id'];
            unset($data['employee_id']);

            return $this->getModel()->updateOrCreate(
                ['employee_id' => $employeeId],
                $data
            );
        });
    }

    /**
     * Bulk upsert employees by employee_id.
     */
    public function bulkUpsertByEmployeeId(array $employeesData): Collection
    {
        return $this->dbManager->transaction(function () use ($employeesData) {
            $results = collect();

            foreach ($employeesData as $employeeData) {
                $employeeId = $employeeData['employee_id'];
                unset($employeeData['employee_id']);

                $employee = $this->getModel()->updateOrCreate(
                    ['employee_id' => $employeeId],
                    $employeeData
                );

                $results->push($employee);
            }

            return $results;
        });
    }

    /**
     * Get employees by location.
     */
    public function getByLocation(string $location): Collection
    {
        return $this->newQuery()->where('location', $location)->get();
    }

    /**
     * Get employees by project.
     */
    public function getByProject(string $project): Collection
    {
        return $this->newQuery()
            ->whereJsonContains('projects', $project)
            ->get();
    }

    /**
     * Search employees by name.
     */
    public function searchByName(string $name): Collection
    {
        return $this->newQuery()
            ->where('name', 'like', "%{$name}%")
            ->get();
    }

    /**
     * Get employees with their assigned assets.
     */
    public function getWithAssets(int $employeeId): ?Employee
    {
        return $this->newQuery()
            ->with('assignedAssets')
            ->find($employeeId);
    }

    /**
     * Get employees with their current assets.
     */
    public function getWithCurrentAssets(int $employeeId): ?Employee
    {
        return $this->newQuery()
            ->with('currentAssets')
            ->find($employeeId);
    }

    /**
     * Get employees with audit assignments.
     */
    public function getWithAuditAssignments(int $employeeId): ?Employee
    {
        return $this->newQuery()
            ->with('auditAssignments')
            ->find($employeeId);
    }

    /**
     * Get employees who have assets in specific locations.
     */
    public function getEmployeesWithAssetsInLocations(array $locationNames): Collection
    {
        return $this->newQuery()
            ->whereHas('assignedAssets', function ($query) use ($locationNames) {
                $query->whereIn('location', $locationNames);
            })
            ->get();
    }

    /**
     * Get employees who are auditors.
     */
    public function getAuditors(): Collection
    {
        return $this->newQuery()
            ->whereHas('auditAssignments')
            ->get();
    }

    /**
     * Get employees by multiple criteria.
     */
    public function getByCriteria(array $criteria): Collection
    {
        $query = $this->newQuery();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get();
    }

    /**
     * Get employee statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $byLocation = $this->newQuery()
            ->selectRaw('location, COUNT(*) as count')
            ->whereNotNull('location')
            ->groupBy('location')
            ->pluck('count', 'location')
            ->toArray();

        $withAssets = $this->newQuery()
            ->whereHas('assignedAssets')
            ->count();

        $auditors = $this->getAuditors()->count();

        return [
            'total' => $total,
            'with_assets' => $withAssets,
            'auditors' => $auditors,
            'by_location' => $byLocation,
        ];
    }

    /**
     * Get employees for audit notifications.
     */
    public function getForAuditNotifications(array $locationNames, array $auditorIds): Collection
    {
        // Get all employees who have assets in the audited locations
        $employeesWithAssets = $this->getEmployeesWithAssetsInLocations($locationNames);

        // Also include assigned auditors
        $assignedAuditors = $this->findMany($auditorIds);

        // Merge and deduplicate employees
        return $employeesWithAssets->merge($assignedAuditors)->unique('id');
    }
}
