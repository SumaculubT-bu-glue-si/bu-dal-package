<?php

namespace YourCompany\GraphQLDAL\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'type',
        'hostname',
        'manufacturer',
        'model',
        'part_number',
        'serial_number',
        'form_factor',
        'os',
        'os_bit',
        'office_suite',
        'software_license_key',
        'wired_mac_address',
        'wired_ip_address',
        'wireless_mac_address',
        'wireless_ip_address',
        'purchase_date',
        'purchase_price',
        'purchase_price_tax_included',
        'depreciation_years',
        'depreciation_dept',
        'cpu',
        'memory',
        'location',
        'status',
        'previous_user',
        'user_id',
        'usage_start_date',
        'usage_end_date',
        'carry_in_out_agreement',
        'last_updated',
        'updated_by',
        'notes',
        'project',
        'notes1',
        'notes2',
        'notes3',
        'notes4',
        'notes5',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'usage_start_date' => 'date',
        'usage_end_date' => 'date',
        'purchase_price' => 'decimal:2',
        'purchase_price_tax_included' => 'decimal:2',
        'depreciation_years' => 'integer',
        'last_updated' => 'datetime',
    ];

    /**
     * Check if asset is available for assignment.
     */
    public function isAvailable()
    {
        return in_array($this->status, ['保管中', '保管(使用無)', '返却済']);
    }

    /**
     * Check if asset is currently assigned.
     */
    public function isAssigned()
    {
        return in_array($this->status, ['利用中', '貸出中', '利用予約']);
    }

    /**
     * Get the audit assets for this asset.
     */
    public function auditAssets()
    {
        return $this->hasMany(AuditAsset::class);
    }

    /**
     * Get the audit logs for this asset.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the employee assigned to this asset.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }
}
