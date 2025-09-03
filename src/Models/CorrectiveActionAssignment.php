<?php

namespace Bu\DAL\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveActionAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'corrective_action_id',
        'audit_assignment_id',
        'assigned_to_employee_id',
        'status',
        'started_at',
        'completed_at',
        'progress_notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the corrective action this assignment belongs to.
     */
    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }

    /**
     * Get the audit assignment this corrective action is linked to.
     */
    public function auditAssignment(): BelongsTo
    {
        return $this->belongsTo(AuditAssignment::class);
    }

    /**
     * Get the employee assigned to complete this corrective action.
     */
    public function assignedToEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to_employee_id');
    }

    /**
     * Check if the assignment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the assignment is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the assignment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    /**
     * Mark the assignment as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the assignment as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Update progress notes.
     */
    public function updateProgressNotes(string $notes): void
    {
        $this->update(['progress_notes' => $notes]);
    }
}
