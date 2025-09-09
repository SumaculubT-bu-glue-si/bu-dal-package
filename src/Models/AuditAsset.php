<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Bu\Server\Traits\Auditable;

class AuditAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_plan_id',
        'asset_id',
        'original_location',
        'original_user',
        'current_status',
        'auditor_notes',
        'audited_at',
        'resolved',
        'audit_status',
        'audited_by',
        'current_location',
        'current_user',
    ];

    protected $casts = [
        'audited_at' => 'datetime',
        'resolved' => 'boolean',
    ];

    /**
     * Get the audit plan this asset belongs to.
     */
    public function auditPlan(): BelongsTo
    {
        return $this->belongsTo(AuditPlan::class);
    }

    /**
     * Get the asset being audited.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the corrective actions for this audit asset.
     */
    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    /**
     * Check if the asset has been audited.
     */
    public function isAudited(): bool
    {
        return !is_null($this->audited_at);
    }

    /**
     * Check if all corrective actions for this asset are completed.
     */
    public function allCorrectiveActionsCompleted(): bool
    {
        return $this->correctiveActions()
            ->where('status', '!=', 'completed')
            ->count() === 0;
    }

    /**
     * Check if the asset is fully resolved (audited + all actions completed).
     */
    public function isFullyResolved(): bool
    {
        return $this->isAudited() && $this->allCorrectiveActionsCompleted();
    }

    /**
     * Update the main asset table when all issues are resolved.
     */
    public function updateMainAsset(): bool
    {
        if (!$this->isFullyResolved()) {
            return false;
        }

        return $this->updateMainAssetDirectly();
    }

    /**
     * Update the main asset table directly (for immediate resolution).
     */
    public function updateMainAssetDirectly(): bool
    {
        $asset = $this->asset;
        if (!$asset) {
            return false;
        }

        // Build update data for the main asset
        $updateData = [
            'location' => $this->current_location ?: $this->original_location,
            'status' => $this->getResolutionStatus(),
            'last_updated' => now(),
            'updated_by' => $this->audited_by ?? 'System',
            'notes' => $this->auditor_notes,
        ];

        // Try to resolve user ID from name if we have names instead of IDs
        $userId = $this->resolveUserId($this->current_user ?: $this->original_user);
        if ($userId) {
            $updateData['user_id'] = $userId;
        }
        // If we can't resolve the user ID, we skip updating user_id to avoid the error

        // Add specific notes for User and Location changes
        $changeNotes = [];
        if ($this->hasLocationChanged()) {
            $changeNotes[] = "Location changed from '{$this->original_location}' to '{$this->current_location}' - verified during audit";
        }
        if ($this->hasUserChanged()) {
            $changeNotes[] = "User changed from '{$this->original_user}' to '{$this->current_user}' - verified during audit";
        }
        
        if (!empty($changeNotes)) {
            $updateData['notes'] = ($updateData['notes'] ? $updateData['notes'] . "\n\n" : '') . implode("\n", $changeNotes);
        }

        // Update the main asset with the final audit results
        $asset->update($updateData);

        // Mark audit asset as fully resolved
        $this->update(['resolved' => true]);

        return true;
    }

    /**
     * Get the appropriate status for the main asset when this discrepancy is resolved.
     */
    public function getResolutionStatus(): string
    {
        // Determine the appropriate status based on the type of discrepancy
        if ($this->current_status === '欠落' || $this->current_status === 'Missing') {
            // If it was missing and now found, set to "In Storage" (保管中)
            return '保管中';
        }
        
        if ($this->current_status === '故障中' || $this->current_status === 'Broken') {
            // If it was broken and now resolved, set to "In Use" (利用中)
            return '利用中';
        }
        
        if ($this->hasLocationChanged() || $this->hasUserChanged()) {
            // For location or user changes, keep the asset "In Use" (利用中)
            return '利用中';
        }
        
        // Default to the current status if no specific logic applies
        return $this->current_status;
    }

    /**
     * Get the audit summary for this asset.
     */
    public function getAuditSummary(): array
    {
        $totalActions = $this->correctiveActions()->count();
        $completedActions = $this->correctiveActions()->where('status', 'completed')->count();
        $pendingActions = $totalActions - $completedActions;

        return [
            'audited' => $this->isAudited(),
            'total_corrective_actions' => $totalActions,
            'completed_actions' => $completedActions,
            'pending_actions' => $pendingActions,
            'fully_resolved' => $this->isFullyResolved(),
            'resolved' => $this->resolved,
            'audit_status' => $this->audit_status,
        ];
    }

    /**
     * Get the status transition history for this asset.
     */
    public function getStatusTransitionHistory(): array
    {
        $history = [];
        
        // Original status when audit plan was created
        $history[] = [
            'timestamp' => $this->created_at,
            'status' => $this->original_status ?? 'Unknown',
            'event' => 'Audit plan created',
            'notes' => 'Initial status recorded'
        ];

        // Current status after audit
        if ($this->audited_at) {
            $history[] = [
                'timestamp' => $this->getRawOriginal('current_status'),
                'status' => $this->current_status,
                'event' => 'Asset audited',
                'notes' => $this->auditor_notes
            ];
        }

        // Status changes from corrective actions
        $this->correctiveActions()
            ->where('status', 'completed')
            ->orderBy('completed_date')
            ->get()
            ->each(function ($action) use (&$history) {
                $history[] = [
                    'timestamp' => $action->completed_date,
                    'status' => $action->getResolutionStatus(),
                    'event' => 'Corrective action completed',
                    'notes' => $action->notes
                ];
            });

        return $history;
    }

    /**
     * Check if the asset status indicates an issue.
     */
    public function hasIssue(): bool
    {
        return in_array($this->current_status, [
            '故障中', // Broken
            '廃止'   // Abolished
        ]);
    }

    /**
     * Check if the asset location has changed since the audit plan was created.
     */
    public function hasLocationChanged(): bool
    {
        return $this->current_location &&
            $this->current_location !== $this->original_location;
    }

    /**
     * Check if the asset user has changed since the audit plan was created.
     */
    public function hasUserChanged(): bool
    {
        return $this->current_user &&
            $this->current_user !== $this->original_user;
    }

    /**
     * Get the status badge variant for UI display.
     */
    public function getStatusBadgeVariant(): string
    {
        return match ($this->current_status) {
            '返却済' => 'success',      // Returned
            '廃止' => 'secondary',      // Abolished
            '保管(使用無)' => 'info',   // In Storage - Unused
            '利用中' => 'primary',      // In Use
            '保管中' => 'info',         // In Storage
            '貸出中' => 'warning',      // On Loan
            '故障中' => 'destructive',  // Broken
            '利用予約' => 'secondary',  // Reserved for Use
            default => 'secondary'
        };
    }

    /**
     * Mark the asset as audited.
     */
    public function markAsAudited(string $auditor, string $status, ?string $notes = null): void
    {
        $this->update([
            'current_status' => $status,
            'auditor_notes' => $notes,
            'audited_at' => now(),
            'audited_by' => $auditor,
            'audit_status' => true, // Mark as audited
        ]);
    }

    /**
     * Automatically create corrective actions based on audit findings.
     */
    public function createCorrectiveActionsFromAudit(): array
    {
        $actions = [];
        
        // Check for missing asset
        if ($this->current_status === '欠落') {
            $actions[] = [
                'issue' => "Asset {$this->asset->asset_id} ({$this->asset->model}) is missing from expected location: {$this->original_location}",
                'action' => "Locate the asset and return it to the correct location, or update asset records if permanently moved",
                'priority' => 'high',
                'due_date' => now()->addDays(7),
            ];
        }

        // Check for broken asset
        if ($this->current_status === '故障中') {
            $actions[] = [
                'issue' => "Asset {$this->asset->asset_id} ({$this->asset->model}) requires repair or replacement",
                'action' => "Schedule repair assessment or arrange for replacement if beyond repair",
                'priority' => 'medium',
                'due_date' => now()->addDays(14),
            ];
        }

        // Check for location change
        if ($this->hasLocationChanged()) {
            $actions[] = [
                'issue' => "Asset {$this->asset->asset_id} ({$this->asset->model}) location changed from {$this->original_location} to {$this->current_location}",
                'action' => "Verify the new location is authorized and update asset records accordingly",
                'priority' => 'low',
                'due_date' => now()->addDays(3),
            ];
        }

        // Check for user change
        if ($this->hasUserChanged()) {
            $actions[] = [
                'issue' => "Asset {$this->asset->asset_id} ({$this->asset->model}) user changed from {$this->original_user} to {$this->current_user}",
                'action' => "Verify the new user assignment is authorized and update asset records",
                'priority' => 'low',
                'due_date' => now()->addDays(3),
            ];
        }

        // Create the corrective actions
        foreach ($actions as $actionData) {
            \Bu\Server\Models\CorrectiveAction::create([
                'audit_asset_id' => $this->id,
                'audit_plan_id' => $this->audit_plan_id,
                'issue' => $actionData['issue'],
                'action' => $actionData['action'],
                'priority' => $actionData['priority'],
                'status' => 'pending',
                'due_date' => $actionData['due_date'],
                'assigned_to' => $this->auditPlan->assigned_employee_id ?? null,
            ]);
        }

        return $actions;
    }

    /**
     * Resolve user ID from user name or ID.
     */
    private function resolveUserId($userIdentifier): ?int
    {
        if (!$userIdentifier) {
            return null;
        }

        // If it's already a numeric ID, return it
        if (is_numeric($userIdentifier)) {
            return (int) $userIdentifier;
        }

        // If it's a name, try to find the user ID
        try {
            // Look for employee by name (assuming the name is stored in employees table)
            $employee = \Bu\Server\Models\Employee::where('name', $userIdentifier)->first();
            if ($employee) {
                return $employee->id;
            }

            // If not found, return null (we won't update user_id)
            return null;
        } catch (\Exception $e) {
            // Log the error but don't fail the update
            \Illuminate\Support\Facades\Log::warning("Could not resolve user ID for name: {$userIdentifier}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user information for display purposes.
     */
    public function getUserInfo(): array
    {
        $currentUserId = $this->resolveUserId($this->current_user);
        $originalUserId = $this->resolveUserId($this->original_user);

        return [
            'current_user' => [
                'id' => $currentUserId,
                'name' => $this->current_user,
                'is_id' => is_numeric($this->current_user)
            ],
            'original_user' => [
                'id' => $originalUserId,
                'name' => $this->original_user,
                'is_id' => is_numeric($this->original_user)
            ]
        ];
    }

    /**
     * Get a summary of what happens when discrepancies are resolved.
     */
    public function getDiscrepancyResolutionSummary(): array
    {
        $summary = [];
        
        if ($this->current_status === '欠落') {
            $summary[] = [
                'type' => 'Missing',
                'action' => 'Asset found and returned to storage',
                'new_status' => '保管中 (In Storage)',
                'description' => 'When a missing asset is found, it will be marked as "In Storage" until reassigned'
            ];
        }
        
        if ($this->current_status === '故障中') {
            $summary[] = [
                'type' => 'Broken',
                'action' => 'Asset repaired or replaced',
                'new_status' => '利用中 (In Use)',
                'description' => 'When a broken asset is repaired, it will be marked as "In Use"'
            ];
        }
        
        if ($this->hasLocationChanged()) {
            $summary[] = [
                'type' => 'Location Change',
                'action' => 'Location change verified and confirmed',
                'new_status' => '利用中 (In Use)',
                'description' => 'The new location will be updated in the main assets table after verification'
            ];
        }
        
        if ($this->hasUserChanged()) {
            $summary[] = [
                'type' => 'User Change',
                'action' => 'User change verified and confirmed',
                'new_status' => '利用中 (In Use)',
                'description' => 'The new user assignment will be updated in the main assets table after verification'
            ];
        }
        
        return $summary;
    }
}