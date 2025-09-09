<?php

namespace Bu\Server\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Bu\Server\Traits\Auditable;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'visible',
        'order',
    ];

    protected $casts = [
        'visible' => 'boolean',
        'order' => 'integer',
    ];
}
