<?php

namespace Bu\DAL\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_plan_id',
        'location_id',
        'auditor_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the audit plan this assignment belongs to.
     */
    public function auditPlan(): BelongsTo
    {
        return $this->belongsTo(AuditPlan::class);
    }

    /**
     * Get the location assigned for this audit.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the auditor assigned to this location.
     */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'auditor_id');
    }

    /**
     * Get the corrective action assignments for this audit assignment.
     */
    public function correctiveActionAssignments(): HasMany
    {
        return $this->hasMany(CorrectiveActionAssignment::class);
    }

    /**
     * Check if the assignment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'Completed';
    }

    /**
     * Check if the assignment is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'In Progress';
    }

    /**
     * Get the assets that need to be audited at this location for this plan.
     */
    public function getAssetsToAudit()
    {
        return Asset::where('location', $this->location->name)
            ->whereHas('auditAssets', function ($query) {
                $query->where('audit_plan_id', $this->audit_plan_id);
            })
            ->get();
    }
}
