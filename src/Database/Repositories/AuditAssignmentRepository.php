<?php

namespace Bu\DAL\Database\Repositories;

use Bu\DAL\Models\AuditAssignment;
use Illuminate\Database\Eloquent\Collection;

class AuditAssignmentRepository extends BaseRepository
{
    public function __construct(AuditAssignment $model)
    {
        parent::__construct($model);
    }

    /**
     * Get assignments by audit plan.
     */
    public function getByAuditPlan(int $auditPlanId): Collection
    {
        return $this->model->where('audit_plan_id', $auditPlanId)->get();
    }

    /**
     * Get assignments by auditor.
     */
    public function getByAuditor(int $auditorId): Collection
    {
        return $this->model->where('auditor_id', $auditorId)->get();
    }

    /**
     * Get assignments by location.
     */
    public function getByLocation(int $locationId): Collection
    {
        return $this->model->where('location_id', $locationId)->get();
    }

    /**
     * Get assignments by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get completed assignments.
     */
    public function getCompleted(): Collection
    {
        return $this->model->where('status', 'Completed')->get();
    }

    /**
     * Get in-progress assignments.
     */
    public function getInProgress(): Collection
    {
        return $this->model->where('status', 'In Progress')->get();
    }

    /**
     * Get assigned (pending) assignments.
     */
    public function getAssigned(): Collection
    {
        return $this->model->where('status', 'Assigned')->get();
    }

    /**
     * Get assignment with details.
     */
    public function getWithDetails(int $id): ?AuditAssignment
    {
        return $this->model->with(['auditPlan', 'location', 'auditor'])
            ->find($id);
    }

    /**
     * Complete an assignment.
     */
    public function complete(int $id, ?string $notes = null): bool
    {
        $assignment = $this->find($id);
        if (!$assignment) {
            return false;
        }

        return $assignment->update([
            'status' => 'Completed',
            'notes' => $notes
        ]);
    }
}