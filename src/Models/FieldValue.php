<?php

namespace AuroraWebSoftware\FlexyField\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string $name
 * @property string|null $schema_code
 * @property int|null $schema_id
 * @property \Illuminate\Support\Carbon|null $value_date
 * @property \Illuminate\Support\Carbon|null $value_datetime
 * @property string|null $value_decimal
 * @property int|null $value_int
 * @property string|null $value_string
 * @property bool|null $value_boolean
 * @property array<string, mixed>|null $value_json
 */
class FieldValue extends Model
{
    protected $table = 'ff_field_values';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'value_boolean' => 'boolean',
        'value_datetime' => 'datetime',
        'value_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Auto-populate schema_id from schema_code if not set
        static::creating(function (FieldValue $fieldValue) {
            if (! $fieldValue->schema_id && $fieldValue->schema_code) {
                $schema = FieldSchema::where('schema_code', $fieldValue->schema_code)->first();
                if ($schema) {
                    $fieldValue->schema_id = $schema->id;
                }
            }
        });

        static::updating(function (FieldValue $fieldValue) {
            // Auto-update schema_id if schema_code changed
            if ($fieldValue->isDirty('schema_code') && $fieldValue->schema_code) {
                $schema = FieldSchema::where('schema_code', $fieldValue->schema_code)->first();
                if ($schema) {
                    $fieldValue->schema_id = $schema->id;
                }
            }
        });
    }

    /**
     * Get the schema this value belongs to
     *
     * @return BelongsTo<FieldSchema, FieldValue>
     *
     * @phpstan-return BelongsTo<FieldSchema, $this>
     */
    public function schema(): BelongsTo
    {
        return $this->belongsTo(FieldSchema::class, 'schema_code', 'schema_code');
    }
}
