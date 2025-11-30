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
     * @return array<int, string|callable>
     */
    public function getValidationRulesArray(): array
    {
        $rules = [];

        // Get base validation rules
        if (! empty($this->validation_rules)) {
            // validation_rules can be stored as a string (pipe-separated rules) or as an array
            if (is_array($this->validation_rules)) {
                $rules = $this->validation_rules;
            } else {
                $rules = explode('|', (string) $this->validation_rules);
            }
        }

        // Add options validation if options are defined
        if ($this->hasOptions()) {
            $options = $this->getOptions();
            $allowedValues = $this->getOptionsKeys($options);

            if ($this->isMultiSelect()) {
                // Multi-select: must be array and all values must be in options
                $rules[] = 'array';
                $rules[] = function ($attribute, $value, $fail) use ($allowedValues) {
                    if (! is_array($value)) {
                        return; // 'array' rule handles this
                    }

                    foreach ($value as $item) {
                        if (! in_array($item, $allowedValues, true)) {
                            $fail("The selected {$attribute} contains an invalid value.");

                            return;
                        }
                    }
                };
            } else {
                // Single select: value must be in options
                $rules[] = function ($attribute, $value, $fail) use ($allowedValues) {
                    if (! in_array($value, $allowedValues, true)) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                };
            }
        }

        return $rules;
    }

    /**
     * Get options array from metadata
     *
     * @return array<int|string, string>
     */
    public function getOptions(): array
    {
        if (! isset($this->metadata['options'])) {
            return [];
        }

        $options = $this->metadata['options'];

        if (! is_array($options)) {
            return [];
        }

        return $options;
    }

    /**
     * Check if field has options defined
     */
    public function hasOptions(): bool
    {
        return ! empty($this->getOptions());
    }

    /**
     * Check if this field allows multiple selections
     */
    public function isMultiSelect(): bool
    {
        return isset($this->metadata['multiple']) && $this->metadata['multiple'] === true;
    }

    /**
     * Get option keys for validation (handles both indexed and associative arrays)
     *
     * @param  array<int|string, string>  $options
     * @return array<int, int|string>
     */
    protected function getOptionsKeys(array $options): array
    {
        // For associative arrays, use keys; for indexed arrays, use values
        return array_is_list($options) ? $options : array_keys($options);
    }

    /**
     * Get the group name for this field
     */
    public function getGroup(): ?string
    {
        $group = $this->metadata['group'] ?? null;

        // Treat empty strings as ungrouped
        if ($group === '' || $group === null) {
            return null;
        }

        return (string) $group;
    }

    /**
     * Check if this field belongs to a group
     */
    public function hasGroup(): bool
    {
        return $this->getGroup() !== null;
    }
}
