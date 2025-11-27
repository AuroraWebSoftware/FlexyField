<?php

namespace AuroraWebSoftware\FlexyField\Models;

use Illuminate\Database\Eloquent\Model;

class Value extends Model
{
    protected $table = 'ff_values';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'value_boolean' => 'boolean',
    ];
}
