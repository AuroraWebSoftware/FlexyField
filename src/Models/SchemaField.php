<?php

namespace AuroraWebSoftware\FlexyField\Models;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $schema_code
 * @property int|null $schema_id
 * @property string $name
 * @property FlexyFieldType $type
 * @property int $sort
 * @property string|array<int, string>|null $validation_rules
 * @property array<string, string>|null $validation_messages
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SchemaField extends Model
{
    protected $table = 'ff_schema_fields';

    protected $guarded = [];

    protected $casts = [
        'validation_messages' => 'array',
        'metadata' => 'array',
        'type' => FlexyFieldType::class,
    ];

    protected static function booted(): void
    {
        // Validate type enum values
        static::creating(function (SchemaField $schemaField) {
            self::validateFieldType($schemaField->type);

            // Auto-populate schema_id from schema_code if not set
            if (! $schemaField->schema_id && $schemaField->schema_code) {
                $schema = FieldSchema::where('schema_code', $schemaField->schema_code)->first();
                if ($schema) {
                    $schemaField->schema_id = $schema->id;
                }
            }
        });

        static::updating(function (SchemaField $schemaField) {
            if ($schemaField->isDirty('type')) {
                self::validateFieldType($schemaField->type);
            }

            // Auto-update schema_id if schema_code changed
            if ($schemaField->isDirty('schema_code') && $schemaField->schema_code) {
                $schema = FieldSchema::where('schema_code', $schemaField->schema_code)->first();
                if ($schema) {
                    $schemaField->schema_id = $schema->id;
                }
            }
        });
    }

    /**
     * Get the schema that owns this field
     *
     * @return BelongsTo<FieldSchema, SchemaField>
     *
     * @phpstan-return BelongsTo<FieldSchema, $this>
     */
    public function schema(): BelongsTo
    {
        return $this->belongsTo(FieldSchema::class, 'schema_id', 'id');
    }

    /**
     * Validate field type is a valid enum value
     */
    protected static function validateFieldType(mixed $fieldType): void
    {
        if (! $fieldType instanceof FlexyFieldType) {
            throw new \InvalidArgumentException("Invalid type: {$fieldType}. Must be a valid FlexyFieldType enum.");
        }
    }

    /**
     * Get validation rules as array
     *
     * @return array<int, string>
     */
    public function getValidationRulesArray(): array
    {
        if (empty($this->validation_rules)) {
            return [];
        }

        // validation_rules can be stored as a string (pipe-separated rules) or as an array
        if (is_array($this->validation_rules)) {
            return $this->validation_rules;
        }

        return explode('|', (string) $this->validation_rules);
    }
}
