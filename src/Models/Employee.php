<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Bu\Server\Traits\Auditable;
use Illuminate\Notifications\Notifiable;

class Employee extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'location',
        'org_unit_path',
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

    /**
     * Get available organizational units
     */
    public static function getAvailableOrgUnits(): array
    {
        return [
            '/' => 'Root Organization',
            '/テスト' => 'テスト (Test)',
            '/一般' => '一般 (General)',
            '/営業' => '営業 (Sales)',
            '/開発' => '開発 (Development)',
            '/管理' => '管理 (Administration)',
        ];
    }
}