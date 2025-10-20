<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $table = 'licenses';

    protected $fillable = [
        'service_subscription_id',
        'account_id',
        'unit_price',
        'currency',
        'billing_cycle',
        'billing_interval',
        'start_date',
        'end_date',
        'renewal_date',
        'version',
        'license_key',
        'used',
        'assigned_employee_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_date' => 'date',
        'used' => 'boolean',
    ];

    public function subscription()
    {
        return $this->belongsTo(ServiceSubscription::class, 'service_subscription_id');
    }

    public function assignedEmployee()
    {
        // assigned_employee_id stores employees.employee_id (string)
        return $this->belongsTo(Employee::class, 'assigned_employee_id', 'employee_id');
    }
}
