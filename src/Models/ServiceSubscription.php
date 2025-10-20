<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSubscription extends Model
{
    use HasFactory;

    protected $table = 'service_subscriptions';

    protected $fillable = [
        'service_name',
        'vendor',
        'license_type',
        'pricing_type',
        'status',
        'category',
        'payment_method',
        'cancellation_date',
        'official_website',
        'official_support',
        'notes',
        'per_seat_monthly_price',
        'per_seat_yearly_price',
        'per_seat_currency',
    ];

    protected $casts = [
        'cancellation_date' => 'date',
        'per_seat_monthly_price' => 'integer',
        'per_seat_yearly_price' => 'integer',
    ];

    public function licenses()
    {
        return $this->hasMany(License::class, 'service_subscription_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_service_subscription', 'service_subscription_id', 'employee_id', 'id', 'employee_id')
            ->withTimestamps();
    }
}
