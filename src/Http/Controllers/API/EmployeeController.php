<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Models\Employee;
use Bu\Server\Http\Requests\EmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends ApiController
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        try {
            $query = Employee::query();

            // Apply filters
            if ($request->has('department')) {
                $query->where('department', $request->department);
            }

            if ($request->has('location')) {
                $query->where('location', $request->location);
            }

            if ($request->has('location_id')) {
                $query->where('location_id', $request->location_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('supervisor_id')) {
                $query->where('supervisor_id', $request->supervisor_id);
            }

            // Search by name or employee ID
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }

            $employees = $query->get();

            // If we're querying by location and got no results, return an empty array instead of failing
            if ($request->has('location') && $employees->isEmpty()) {
                Log::info("No employees found for location: " . $request->location);
                return $this->successResponse([]);
            }

            return $this->successResponse($employees);
        } catch (\Exception $e) {
            Log::error('Error fetching employees: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Return a user-friendly error message
            return $this->successResponse(
                [],
                'No employees found for the specified location. Some employees might not have location data.'
            );
        }
    }

    /**
     * Store a new employee
     *
     * @param EmployeeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(EmployeeRequest $request)
    {
        try {
            $employee = Employee::create($request->validated());
            return $this->successResponse($employee, 'Employee created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error creating employee: ' . $e->getMessage());
            return $this->errorResponse('Failed to create employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee)
    {
        $employee->load(['location', 'supervisor', 'subordinates', 'assets']);

        return $this->successResponse($employee);
    }

    /**
     * Update the specified employee
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return $this->successResponse($employee, 'Employee updated successfully');
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee)
    {
        // Check for subordinates
        if ($employee->subordinates()->exists()) {
            return $this->errorResponse('Cannot delete employee with subordinates. Please reassign subordinates first.', 422);
        }

        // Check for assigned assets
        if ($employee->assets()->exists()) {
            return $this->errorResponse('Cannot delete employee with assigned assets. Please reassign assets first.', 422);
        }

        $employee->delete();

        return $this->successResponse(null, 'Employee deleted successfully');
    }

    /**
     * Get employee hierarchy
     */
    public function hierarchy(Request $request)
    {
        $query = Employee::whereNull('supervisor_id');

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        $employees = $query->with('subordinates.subordinates')
            ->get();

        return $this->successResponse($employees);
    }

    /**
     * Get employee assets
     */
    public function assets(Employee $employee)
    {
        $assets = $employee->assets()
            ->with('location')
            ->paginate(15);

        return $this->successResponse($assets);
    }
}
