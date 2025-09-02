<?php

namespace YourCompany\GraphQLDAL\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
