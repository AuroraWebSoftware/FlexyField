<?php

namespace AuroraWebSoftware\FlexyField\Models;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $set_code
 * @property string $field_name
 * @property FlexyFieldType $field_type
 * @property int $sort
 * @property string|array<int, string>|null $validation_rules
 * @property array<string, string>|null $validation_messages
 * @property array<string, mixed>|null $field_metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SetField extends Model
{
    protected $table = 'ff_set_fields';

    protected $guarded = [];

    protected $casts = [
        'validation_messages' => 'array',
        'field_metadata' => 'array',
        'field_type' => FlexyFieldType::class,
    ];

    protected static function booted(): void
    {
        // Validate field_type enum values
        static::creating(function (SetField $setField) {
            self::validateFieldType($setField->field_type);
        });

        static::updating(function (SetField $setField) {
            if ($setField->isDirty('field_type')) {
                self::validateFieldType($setField->field_type);
            }
        });
    }

    /**
     * Get the field set that owns this field
     *
     * @return BelongsTo<FieldSet, SetField>
     * @phpstan-return BelongsTo<FieldSet, $this>
     */
    public function fieldSet(): BelongsTo
    {
        return $this->belongsTo(FieldSet::class, 'set_code', 'set_code');
    }

    /**
     * Validate field type is a valid enum value
     */
    protected static function validateFieldType(mixed $fieldType): void
    {
        if (! $fieldType instanceof FlexyFieldType) {
            throw new \InvalidArgumentException("Invalid field_type: {$fieldType}. Must be a valid FlexyFieldType enum.");
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
