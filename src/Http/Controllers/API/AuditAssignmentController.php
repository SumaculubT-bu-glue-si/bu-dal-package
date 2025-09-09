<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Http\Controllers\Controller;
use Bu\Server\Models\AuditAssignment;
use Bu\Server\Models\AuditPlan;
use Bu\Server\Http\Requests\AuditAssignmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditAssignmentController extends Controller
{
    /**
     * Display a listing of audit assignments.
     */
    public function index(Request $request): JsonResponse
    {
        $assignments = AuditAssignment::with(['auditor', 'location', 'auditPlan'])
            ->when($request->plan_id, function ($query, $planId) {
                return $query->where('audit_plan_id', $planId);
            })
            ->when($request->auditor_id, function ($query, $auditorId) {
                return $query->where('auditor_id', $auditorId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($assignments);
    }

    /**
     * Store a newly created audit assignment.
     */
    public function store(AuditAssignmentRequest $request): JsonResponse
    {
        $assignment = AuditAssignment::create($request->validated());
        
        // Update audit plan status if this is the first assignment
        $plan = AuditPlan::find($request->input('audit_plan_id'));
        if ($plan && $plan->status === 'Planning') {
            $plan->update(['status' => 'In Progress']);
        }

        return response()->json($assignment->load(['auditor', 'location', 'auditPlan']), 201);
    }

    /**
     * Display the specified audit assignment.
     */
    public function show(AuditAssignment $assignment): JsonResponse
    {
        return response()->json($assignment->load(['auditor', 'location', 'auditPlan']));
    }

    /**
     * Update the specified audit assignment.
     */
    public function update(AuditAssignmentRequest $request, AuditAssignment $assignment): JsonResponse
    {
        $assignment->update($request->validated());
        return response()->json($assignment->load(['auditor', 'location', 'auditPlan']));
    }

    /**
     * Remove the specified audit assignment.
     */
    public function destroy(AuditAssignment $assignment): JsonResponse
    {
        $assignment->delete();
        return response()->json(null, 204);
    }

    /**
     * Bulk create audit assignments.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'audit_plan_id' => 'required|exists:audit_plans,id',
            'assignments' => 'required|array',
            'assignments.*.auditor_id' => 'required|exists:employees,id',
            'assignments.*.location_id' => 'required|exists:locations,id'
        ]);

        $assignments = collect($request->input('assignments'))->map(function ($assignment) use ($request) {
            return AuditAssignment::create([
                'audit_plan_id' => $request->input('audit_plan_id'),
                'auditor_id' => $assignment['auditor_id'],
                'location_id' => $assignment['location_id']
            ]);
        });

        // Update audit plan status
        $plan = AuditPlan::find($request->input('audit_plan_id'));
        if ($plan && $plan->status === 'Planning') {
            $plan->update(['status' => 'In Progress']);
        }

        return response()->json([
            'message' => 'Assignments created successfully',
            'assignments' => $assignments
        ], 201);
    }

    /**
     * Update assignment status.
     */
    public function updateStatus(Request $request, AuditAssignment $assignment): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed'
        ]);

        $assignment->update(['status' => $request->input('status')]);
        return response()->json($assignment->load(['auditor', 'location', 'auditPlan']));
    }
}
