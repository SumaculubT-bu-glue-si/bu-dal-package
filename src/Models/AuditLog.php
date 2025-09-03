<?php

namespace Bu\DAL\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_plan_id',
        'asset_id',
        'action',
        'old_values',
        'new_values',
        'performed_by',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the audit plan this log belongs to.
     */
    public function auditPlan(): BelongsTo
    {
        return $this->belongsTo(AuditPlan::class);
    }

    /**
     * Get the asset this log relates to (if any).
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function performer(): BelongsTo
    {
        // If performed_by is numeric, it's a user ID, otherwise it's a system identifier
        if (is_numeric($this->performed_by)) {
            return $this->belongsTo(User::class, 'performed_by');
        }

        // Return null for non-numeric performed_by values
        return $this->belongsTo(User::class, 'performed_by')->whereRaw('1 = 0');
    }

    /**
     * Check if this log entry has changes to track.
     */
    public function hasChanges($changes = null, $attributes = null): bool
    {
        // If called with parameters, use parent method
        if ($changes !== null || $attributes !== null) {
            return parent::hasChanges($changes, $attributes);
        }

        // Custom logic for audit log changes
        return !empty($this->old_values) && !empty($this->new_values);
    }

    /**
     * Get a human-readable summary of the changes.
     */
    public function getChangeSummary(): string
    {
        if (!$this->hasChanges()) {
            return $this->description ?? $this->action;
        }

        $changes = [];
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = ucfirst($field) . ': ' . ($oldValue ?? 'null') . ' → ' . ($newValue ?? 'null');
            }
        }

        return implode(', ', $changes);
    }

    /**
     * Create a new audit log entry.
     */
    public static function log(
        int $auditPlanId,
        string $action,
        string $performedBy,
        ?int $assetId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): self {
        return self::create([
            'audit_plan_id' => $auditPlanId,
            'asset_id' => $assetId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'performed_by' => $performedBy,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get logs for a specific audit plan.
     */
    public static function forAuditPlan(int $auditPlanId)
    {
        return self::where('audit_plan_id', $auditPlanId)
            ->with(['asset', 'performer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get logs for a specific asset.
     */
    public static function forAsset(int $assetId)
    {
        return self::where('asset_id', $assetId)
            ->with(['auditPlan', 'performer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
