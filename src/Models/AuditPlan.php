<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Bu\Server\Traits\Auditable;

class AuditPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'due_date',
        'status',
        'created_by',
        'description',
        'calendar_events',
        'chat_space_id',
        'chat_space_name',
        'chat_space_created_at',
        'chat_space_cleanup_scheduled'
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'calendar_events' => 'array',
        'chat_space_created_at' => 'datetime',
        'chat_space_cleanup_scheduled' => 'boolean'
    ];

    /**
     * Get the user who created this audit plan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the audit assignments for this plan.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AuditAssignment::class);
    }

    /**
     * Get the audit assets for this plan.
     */
    public function auditAssets(): HasMany
    {
        return $this->hasMany(AuditAsset::class);
    }

    /**
     * Get the audit logs for this plan.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Calculate the progress percentage of the audit plan.
     */
    public function getProgressAttribute(): int
    {
        $totalAssets = $this->auditAssets()->count();
        if ($totalAssets === 0) {
            return 0;
        }

        $completedAssets = $this->auditAssets()
            ->whereIn('current_status', ['Found', 'In Storage', 'Broken', 'Missing', 'Scheduled for Disposal'])
            ->count();

        return round(($completedAssets / $totalAssets) * 100);
    }

    /**
     * Check if the audit plan is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'Completed';
    }

    /**
     * Get the target locations for this audit plan.
     */
    public function getTargetLocations()
    {
        return $this->assignments()->with('location')->get()->pluck('location');
    }

    /**
     * Get the assigned auditors for this audit plan.
     */
    public function getAssignedAuditors()
    {
        return $this->assignments()->with('auditor')->get()->pluck('auditor');
    }

    /**
     * Get the corrective actions for this plan.
     */
    public function correctiveActions()
    {
        return $this->hasMany(\Bu\Server\Models\CorrectiveAction::class);
    }

    /**
     * Get comprehensive audit plan summary.
     */
    public function getAuditSummary(): array
    {
        $totalAssets = $this->auditAssets()->count();
        $auditedAssets = $this->auditAssets()->where('audit_status', true)->count();
        $resolvedAssets = $this->auditAssets()->where('resolved', true)->count();

        $totalActions = $this->correctiveActions()->count();
        $completedActions = $this->correctiveActions()->where('status', 'completed')->count();
        $pendingActions = $totalActions - $completedActions;

        return [
            'plan_id' => $this->id,
            'plan_name' => $this->name,
            'total_assets' => $totalAssets,
            'audited_assets' => $auditedAssets,
            'resolved_assets' => $resolvedAssets,
            'audit_progress' => $totalAssets > 0 ? round(($auditedAssets / $totalAssets) * 100, 2) : 0,
            'resolution_progress' => $totalAssets > 0 ? round(($resolvedAssets / $totalAssets) * 100, 2) : 0,
            'total_corrective_actions' => $totalActions,
            'completed_actions' => $completedActions,
            'pending_actions' => $pendingActions,
            'action_completion_rate' => $totalActions > 0 ? round(($completedActions / $totalActions) * 100, 2) : 0,
        ];
    }
}