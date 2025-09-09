<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Bu\Server\Traits\Auditable;

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

    /**
     * Get the parent location.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /**
     * Get the child locations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }
}
