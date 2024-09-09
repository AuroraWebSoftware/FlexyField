<?php

namespace AuroraWebSoftware\FlexyField\Models;

use Illuminate\Database\Eloquent\Model;

class Shape extends Model
{
    protected $casts = [
        'field_metadata' => 'array',
    ];

    protected $table = 'ff_shapes';

    protected $guarded = [];

    public $timestamps = false;
}
