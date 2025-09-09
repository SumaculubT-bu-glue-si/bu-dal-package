<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Http\Controllers\Controller;
use Bu\Server\Models\CorrectiveActionAssignment;
use Bu\Server\Http\Requests\CorrectiveActionAssignmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorrectiveActionAssignmentController extends Controller
{
    /**
     * Display a listing of corrective action assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $assignments = CorrectiveActionAssignment::with(['employee', 'correctiveAction'])
            ->when($request->action_id, function ($query, $actionId) {
                return $query->where('corrective_action_id', $actionId);
            })
            ->when($request->employee_id, function ($query, $employeeId) {
                return $query->where('employee_id', $employeeId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($assignments);
    }

    /**
     * Store a newly created corrective action assignment.
     */
    public function store(CorrectiveActionAssignmentRequest $request): JsonResponse
    {
        $assignment = CorrectiveActionAssignment::create($request->validated());
        return response()->json($assignment->load(['employee', 'correctiveAction']), 201);
    }

    /**
     * Display the specified corrective action assignment.
     */
    public function show(CorrectiveActionAssignment $assignment): JsonResponse
    {
        return response()->json($assignment->load(['employee', 'correctiveAction']));
    }

    /**
     * Update the specified corrective action assignment.
     */
    public function update(CorrectiveActionAssignmentRequest $request, CorrectiveActionAssignment $assignment): JsonResponse
    {
        $assignment->update($request->validated());
        return response()->json($assignment->load(['employee', 'correctiveAction']));
    }

    /**
     * Remove the specified corrective action assignment.
     */
    public function destroy(CorrectiveActionAssignment $assignment): JsonResponse
    {
        $assignment->delete();
        return response()->json(null, 204);
    }

    /**
     * Get assignments for a specific corrective action.
     */
    public function getActionAssignments(int $actionId): JsonResponse
    {
        $assignments = CorrectiveActionAssignment::with(['employee'])
            ->where('corrective_action_id', $actionId)
            ->get();

        return response()->json($assignments);
    }

    /**
     * Bulk create assignments for a corrective action.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'corrective_action_id' => 'required|exists:corrective_actions,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        $assignments = collect($request->input('employee_ids'))->map(function ($employeeId) use ($request) {
            return CorrectiveActionAssignment::create([
                'corrective_action_id' => $request->input('corrective_action_id'),
                'employee_id' => $employeeId
            ]);
        });

        return response()->json([
            'message' => 'Assignments created successfully',
            'assignments' => $assignments->load(['employee', 'correctiveAction'])
        ], 201);
    }
}
