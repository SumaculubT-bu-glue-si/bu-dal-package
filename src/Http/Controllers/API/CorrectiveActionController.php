<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Http\Controllers\Controller;
use Bu\Server\Models\CorrectiveAction;
use Bu\Server\Http\Requests\CorrectiveActionRequest;
use Bu\Server\Services\CorrectiveActionNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CorrectiveActionController extends Controller
{
    /**
     * Display a listing of corrective actions.
     */
    public function index(Request $request): JsonResponse
    {
        $actions = CorrectiveAction::with(['audit', 'assignedTo'])
            ->when($request->audit_id, function ($query, $auditId) {
                return $query->where('audit_id', $auditId);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($actions);
    }

    /**
     * Store a newly created corrective action.
     */
    protected $notificationService;

    public function __construct(CorrectiveActionNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function store(CorrectiveActionRequest $request): JsonResponse
    {
        $action = CorrectiveAction::create($request->validated());
        $action->load(['auditAsset.asset', 'auditPlan', 'assignedTo']);

        // Send notification
        $result = $this->notificationService->sendCorrectiveActionNotification($action);

        return response()->json([
            'data' => $action,
            'notification' => $result
        ], 201);
    }

    /**
     * Display the specified corrective action.
     */
    public function show(CorrectiveAction $action): JsonResponse
    {
        return response()->json($action->load(['audit', 'assignedTo', 'updates']));
    }

    /**
     * Update the specified corrective action.
     */
    public function update(CorrectiveActionRequest $request, CorrectiveAction $action): JsonResponse
    {
        $action->update($request->validated());
        return response()->json($action->load(['audit', 'assignedTo']));
    }

    /**
     * Remove the specified corrective action.
     */
    public function destroy(CorrectiveAction $action): JsonResponse
    {
        // Load the audit asset before deletion to check if we need to update statuses
        $auditAsset = $action->auditAsset;
        
        $action->delete();
        
        // If this was the last corrective action for the audit asset, update its status
        if ($auditAsset && $auditAsset->correctiveActions()->count() === 0) {
            $auditAsset->update(['resolved' => true]);
            $auditAsset->updateMainAssetDirectly();
        }
        
        return response()->json(null, 204);
    }

    /**
     * Add an update to a corrective action.
     */
    public function addUpdate(Request $request, CorrectiveAction $action): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string',
            'status' => 'required|in:open,in_progress,completed,verified',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240'
        ]);

        $update = $action->updates()->create([
            'comment' => $validated['comment'],
            'status' => $validated['status'],
            'user_id' => $request->user()->id
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $update->addMedia($file)
                    ->toMediaCollection('attachments');
            }
        }

        $action->status = $validated['status'];
        $action->save();

        return response()->json($update, 201);
    }

    /**
     * Get overdue corrective actions.
     */
    public function overdue(): JsonResponse
    {
        $actions = CorrectiveAction::with(['audit', 'assignedTo'])
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'verified'])
            ->orderBy('due_date')
            ->get();

        return response()->json($actions);
    }

    /**
     * Get assignments for a corrective action.
     */
    public function getAssignments(int $id): JsonResponse
    {
        $action = CorrectiveAction::findOrFail($id);
        $assignments = $action->assignments()->with('employee')->get();

        return response()->json($assignments);
    }

    /**
     * Create an assignment for a corrective action.
     */
    public function createAssignment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|string',
            'due_date' => 'required|date|after:today'
        ]);

        $action = CorrectiveAction::findOrFail($id);
        $assignment = $action->assignments()->create($request->all());

        return response()->json($assignment->load('employee'), 201);
    }

    /**
     * Update action status.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateActionStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'action_id' => 'required|integer',
                'status' => 'required|string|in:pending,in_progress,completed',
                'notes' => 'nullable|string',
                'employee_id' => 'required|integer'
            ]);

            $actionId = $request->input('action_id');
            $status = $request->input('status');
            $notes = $request->input('notes');
            $employeeId = $request->input('employee_id');

            $action = CorrectiveAction::findOrFail($actionId);

            // Verify the employee is assigned to this action
            if ($action->assigned_to != $employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this corrective action'
                ], 403);
            }

            // Update the action status
            if ($status === 'completed') {
                // Get the proper resolution status based on the asset's original issue
                $resolutionStatus = $action->getResolutionStatus();
                
                \Illuminate\Support\Facades\Log::info('Processing corrective action completion', [
                    'action_id' => $action->id,
                    'resolution_status' => $resolutionStatus
                ]);

                // Use markAsCompleted to handle all cascading updates
                $success = $action->markAsCompleted($notes, $resolutionStatus);
                
                if (!$success) {
                    throw new \Exception('Failed to complete corrective action and update related tables');
                }

                // Force update the audit asset if needed
                if ($auditAsset = $action->auditAsset) {
                    \Illuminate\Support\Facades\Log::info('Forcing audit asset update', [
                        'audit_asset_id' => $auditAsset->id,
                        'new_status' => $resolutionStatus
                    ]);
                    
                    $auditAsset->update([
                        'current_status' => $resolutionStatus,
                        'resolved' => true,
                        'auditor_notes' => ($auditAsset->auditor_notes ? $auditAsset->auditor_notes . "\n\n" : '') .
                            "[" . now()->format('Y-m-d H:i:s') . "] Corrective action completed. Status updated to: " . $resolutionStatus .
                            ($notes ? "\nResolution notes: " . $notes : '')
                    ]);

                    // Force main asset update
                    $auditAsset->updateMainAssetDirectly();
                }
            } else {
                // For other statuses, just update the corrective action
                $action->status = $status;
                if ($notes) {
                    $action->notes = $action->notes
                        ? $action->notes . "\n[" . now()->format('Y-m-d H:i:s') . "] " . $notes
                        : "[" . now()->format('Y-m-d H:i:s') . "] " . $notes;
                }
                $action->save();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'action' => $action->fresh(),
                    'message' => 'Action status updated successfully'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update action status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update corrective actions.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actions' => 'required|array',
            'actions.*.id' => 'required|exists:corrective_actions,id',
            'actions.*.status' => 'required|in:open,in_progress,completed,verified',
            'actions.*.comment' => 'nullable|string'
        ]);

        $updatedActions = collect($validated['actions'])->map(function ($actionData) {
            $action = CorrectiveAction::find($actionData['id']);
            $notes = $actionData['comment'] ?? 'Status updated via bulk update';

            if ($actionData['status'] === 'completed') {
                // Get resolution status and handle cascading updates
                $resolutionStatus = $action->getResolutionStatus();
                $success = $action->markAsCompleted($notes, $resolutionStatus);
                
                if (!$success) {
                    \Illuminate\Support\Facades\Log::error('Failed to complete corrective action in bulk update', [
                        'action_id' => $action->id
                    ]);
                    throw new \Exception("Failed to complete corrective action {$action->id}");
                }
            } else {
                // For other statuses, create update record and update status
                $update = $action->updates()->create([
                    'comment' => $notes,
                    'status' => $actionData['status'],
                    'user_id' => request()->user()->id
                ]);

                $action->status = $actionData['status'];
                $action->save();
            }

            return $action;
        });

        return response()->json([
            'message' => 'Actions updated successfully',
            'actions' => $updatedActions
        ]);
    }

    /**
     * Get corrective actions for an employee and audit plan.
     */
    public function getEmployeeActions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'audit_plan_id' => 'required|exists:audit_plans,id'
            ]);

            $actions = CorrectiveAction::query()
                ->with(['auditAsset.asset'])
                ->where('audit_plan_id', $request->audit_plan_id)
                ->where('assigned_to', $request->employee_id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($action) {
                    $asset = $action->auditAsset->asset ?? null;
                    return [
                        'id' => $action->id,
                        'audit_asset_id' => $action->audit_asset_id,
                        'issue' => $action->issue,
                        'action' => $action->action,
                        'assigned_to' => $action->assigned_to,
                        'priority' => $action->priority,
                        'status' => $action->status,
                        'due_date' => $action->due_date,
                        'completed_date' => $action->completed_date,
                        'notes' => $action->notes,
                        'created_at' => $action->created_at,
                        'updated_at' => $action->updated_at,
                        'asset' => $asset ? [
                            'asset_id' => $asset->id,
                            'model' => $asset->model,
                            'location' => $asset->location
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'corrective_actions' => $actions
                ]
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch corrective actions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send reminders for specific corrective actions.
     */
    public function sendReminders(Request $request): JsonResponse
    {
        try {
            $actionIds = $request->input('action_ids', []);
            $notificationService = new \Bu\Server\Services\CorrectiveActionNotificationService();

            if (empty($actionIds)) {
                // If no specific actions are provided, send reminders for all overdue actions
                $result = $notificationService->sendOverdueReminders();
            } else {
                // Send reminders for specific actions
                $result = $notificationService->sendBulkNotifications($actionIds);
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'details' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reminders: ' . $e->getMessage()
            ], 500);
        }
    }
}