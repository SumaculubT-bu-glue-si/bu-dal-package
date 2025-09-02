<?php

namespace YourCompany\GraphQLDAL\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'location',
        'projects',
    ];

    protected $casts = [
        'projects' => 'array',
    ];

    /**
     * Get the current assets assigned to this employee.
     */
    public function currentAssets()
    {
        return $this->hasMany(Asset::class, 'user_id')
            ->where('status', 'In Use');
    }

    /**
     * Get the assets currently assigned to this employee.
     */
    public function assignedAssets()
    {
        return $this->hasMany(Asset::class, 'user_id');
    }

    /**
     * Get the audit assignments for this employee.
     */
    public function auditAssignments()
    {
        return $this->hasMany(AuditAssignment::class, 'auditor_id');
    }
}
