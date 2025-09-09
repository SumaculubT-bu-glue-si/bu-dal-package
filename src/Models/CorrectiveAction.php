<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Bu\Server\Traits\Auditable;
use Bu\Server\Services\CorrectiveActionNotificationService;

class CorrectiveAction extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::created(function ($correctiveAction) {
            $notificationService = app(CorrectiveActionNotificationService::class);
            $notificationService->sendCorrectiveActionNotification($correctiveAction);
        });
    }

    protected $fillable = [
        'audit_asset_id',
        'audit_plan_id',
        'issue',
        'action',
        'assigned_to',
        'priority',
        'status',
        'due_date',
        'completed_date',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    /**
     * Get the audit asset this action relates to.
     */
    public function auditAsset(): BelongsTo
    {
        return $this->belongsTo(AuditAsset::class);
    }

    /**
     * Get the audit plan this action belongs to.
     */
    public function auditPlan(): BelongsTo
    {
        return $this->belongsTo(AuditPlan::class);
    }

    /**
     * Get the corrective action assignment for this action.
     */
    public function assignment(): HasOne
    {
        return $this->hasOne(CorrectiveActionAssignment::class);
    }

    /**
     * Get the corrective action assignments for this action.
     */
    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CorrectiveActionAssignment::class);
    }

    /**
     * Scope to filter by audit plan.
     */
    public function scopeForAuditPlan($query, $auditPlanId)
    {
        return $query->where('audit_plan_id', $auditPlanId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority.
     */
    public function scopeWithPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to filter by assignee.
     */
    public function scopeAssignedTo($query, $assignedTo)
    {
        return $query->where('assigned_to', 'like', "%{$assignedTo}%");
    }

    /**
     * Scope to get overdue actions.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'completed');
    }

    /**
     * Check if the action is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() && $this->status !== 'completed';
    }

    /**
     * Mark the action as completed and check if audit asset should be updated.
     */
    public function markAsCompleted(string $notes = null, string $resolutionStatus = null): bool
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'notes' => $notes ? ($this->notes ? $this->notes . "\n\n" : '') . date('Y-m-d H:i:s') . " - " . $notes : $this->notes,
        ]);

        // Update the audit asset status based on resolution
        $auditAsset = $this->auditAsset;
        if ($auditAsset && $resolutionStatus) {
            $auditAsset->update([
                'current_status' => $resolutionStatus,
                'auditor_notes' => ($auditAsset->auditor_notes ? $auditAsset->auditor_notes . "\n\n" : '') .
                    date('Y-m-d H:i:s') . " - Corrective action completed. Status updated to: " . $resolutionStatus .
                    ($notes ? "\nResolution notes: " . $notes : ''),
            ]);
        }

        // Check if all corrective actions for this audit asset are completed
        if ($auditAsset && $auditAsset->allCorrectiveActionsCompleted()) {
            // Update the main asset table
            return $auditAsset->updateMainAsset();
        }

        return true;
    }

    /**
     * Determine the appropriate resolution status based on the original issue.
     */
    public function getResolutionStatus(): string
    {
        $auditAsset = $this->auditAsset;
        if (!$auditAsset) {
            return '利用中'; // Default to "In Use"
        }

        $originalStatus = $auditAsset->current_status;

        // Map broken asset resolutions to appropriate statuses
        return match ($originalStatus) {
            '故障中' => '利用中',        // Broken → In Use (if repaired)
            '欠落' => '保管中',         // Missing → In Storage (if found)
            '廃止' => '保管中',         // Abolished → In Storage (if reactivated)
            default => '利用中'         // Default to "In Use" (for User/Location changes)
        };
    }

    /**
     * Bulk update multiple corrective actions status.
     */
    public static function bulkUpdateStatus(array $actionIds, string $status, string $notes = null): array
    {
        $results = [];

        foreach ($actionIds as $actionId) {
            $action = self::find($actionId);
            if ($action) {
                try {
                    if ($status === 'completed') {
                        $resolutionStatus = $action->getResolutionStatus();
                        $success = $action->markAsCompleted($notes, $resolutionStatus);
                    } else {
                        $action->update(['status' => $status]);
                        if ($notes) {
                            $action->notes = ($action->notes ? $action->notes . "\n\n" : '') . date('Y-m-d H:i:s') . " - " . $notes;
                        }
                        $action->save();
                        $success = true;
                    }

                    $results[] = [
                        'id' => $actionId,
                        'success' => $success,
                        'message' => 'Updated successfully'
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'id' => $actionId,
                        'success' => false,
                        'message' => 'Failed to update: ' . $e->getMessage()
                    ];
                }
            } else {
                $results[] = [
                    'id' => $actionId,
                    'success' => false,
                    'message' => 'Action not found'
                ];
            }
        }

        return $results;
    }

    /**
     * Get the priority color for UI display.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'destructive',
            'high' => 'destructive',
            'medium' => 'default',
            'low' => 'outline',
            default => 'secondary',
        };
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'default',
            'in_progress' => 'default',
            'overdue' => 'destructive',
            'pending' => 'secondary',
            default => 'secondary',
        };
    }
}
