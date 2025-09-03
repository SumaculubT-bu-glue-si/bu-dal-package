<?php

namespace Bu\DAL\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'manager',
        'status',
        'visible',
        'order',
    ];

    protected $casts = [
        'visible' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the assets at this location.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'location', 'name');
    }

    /**
     * Get the audit assignments for this location.
     */
    public function auditAssignments(): HasMany
    {
        return $this->hasMany(AuditAssignment::class);
    }
}
