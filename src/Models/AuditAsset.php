<?php

namespace Bu\DAL\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            $employee = Employee::where('name', $userIdentifier)->first();
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
}
