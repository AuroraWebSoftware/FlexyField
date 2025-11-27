<?php

namespace AuroraWebSoftware\FlexyField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Value extends Model
{
    protected $table = 'ff_values';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'value_boolean' => 'boolean',
    ];

    /**
     * Get the field set this value belongs to
     *
     * @return BelongsTo<FieldSet, Value>
     *
     * @phpstan-return BelongsTo<FieldSet, $this>
     */
    public function fieldSet(): BelongsTo
    {
        return $this->belongsTo(FieldSet::class, 'field_set_code', 'set_code');
    }
}
