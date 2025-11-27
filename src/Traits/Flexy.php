<?php

namespace AuroraWebSoftware\FlexyField\Traits;

use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSetException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetInUseException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetNotFoundException;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldTypeNotAllowedException;
use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
use AuroraWebSoftware\FlexyField\Models\Value;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

trait Flexy
{
    private ?\AuroraWebSoftware\FlexyField\Models\Flexy $fields = null;

    private ?array $creatingFields = null;

    public static function getModelType(): string
    {
        return static::class;
    }

    public static function bootFlexy(): void
    {
        static::addGlobalScope('flexy', function (Builder $builder) {
            $modelType = static::getModelType();
            $builder->leftJoin('ff_values_pivot_view', 'ff_values_pivot_view.model_id', '=', 'id')
                ->where(function ($query) use ($modelType) {
                    $query->where('ff_values_pivot_view.model_type', '=', $modelType)
                        ->orWhereNull('ff_values_pivot_view.model_type');
                });
        });

        // Auto-assign default field set on model creation
        static::creating(function (FlexyModelContract $flexyModelContract) {
            if (! $flexyModelContract->field_set_code) {
                $modelType = static::getModelType();
                $defaultSet = FieldSet::where('model_type', $modelType)
                    ->where('is_default', true)
                    ->first();

                if ($defaultSet) {
                    $flexyModelContract->field_set_code = $defaultSet->set_code;
                }
            }
        });

        static::saving(function (FlexyModelContract $flexyModelContract) {
            if ($flexyModelContract->flexy->isDirty()) {
                $modelType = static::getModelType();
                $dirtyFields = $flexyModelContract->flexy->getDirty() ?? [];

                // Get field set code for this instance
                $fieldSetCode = $flexyModelContract->getFieldSetCode();

                if (! $fieldSetCode) {
                    throw FieldSetNotFoundException::notAssigned($modelType, $flexyModelContract->id ?? 0);
                }

                // Get available fields for this field set
                $setFields = SetField::where('set_code', $fieldSetCode)->get()->keyBy('field_name');

                foreach ($dirtyFields as $field => $value) {
                    // Check if field exists in assigned field set
                    if (! $setFields->has($field)) {
                        $availableFields = $setFields->pluck('field_name')->toArray();
                        throw FieldNotInSetException::forField($field, $fieldSetCode, $availableFields);
                    }

                    $setField = $setFields[$field];

                    // Validate field value
                    if ($setField->validation_rules) {
                        $data = [$field => $value];
                        $validationRules = $setField->getValidationRulesArray();
                        $rules = [$field => $validationRules];
                        $messages = $setField->validation_messages ? [$field => $setField->validation_messages] : [];

                        Validator::make($data, $rules, $messages)->validate();
                    }

                    $addition = [
                        'value_string' => null,
                        'value_int' => null,
                        'value_decimal' => null,
                        'value_datetime' => null,
                        'value_date' => null,
                        'value_boolean' => null,
                        'value_json' => null,
                    ];

                    // Type detection order: most specific to least specific
                    if ($value === null) {
                        // Null values are allowed, all columns remain null
                    } elseif (is_bool($value)) {
                        $addition['value_boolean'] = $value;
                    } elseif (is_int($value)) {
                        $addition['value_int'] = $value;
                    } elseif (is_float($value)) {
                        $addition['value_decimal'] = $value;
                    } elseif ($value instanceof DateTime) {
                        $addition['value_datetime'] = $value;
                    } elseif (is_string($value)) {
                        $addition['value_string'] = $value;
                    } elseif ($value instanceof \Closure) {
                        throw new FlexyFieldTypeNotAllowedException('Closure type is not supported');
                    } elseif (is_array($value) || is_object($value)) {
                        $addition['value_json'] = json_encode($value);
                    } else {
                        throw new FlexyFieldTypeNotAllowedException;
                    }

                    Value::updateOrCreate(
                        [
                            'model_type' => $modelType,
                            'model_id' => $flexyModelContract->id,
                            'field_name' => $field,
                        ],
                        [
                            'model_type' => $modelType,
                            'model_id' => $flexyModelContract->id,
                            'field_name' => $field,
                            'field_set_code' => $fieldSetCode,
                            ...$addition,
                        ]
                    );
                }

                if ($dirtyFields) {
                    $flexyModelContract->flexy->setRawAttributes($flexyModelContract->flexy->getAttributes(), true);

                    // Only recreate view if new fields were added
                    $fieldNames = array_keys($dirtyFields);
                    FlexyField::recreateViewIfNeeded($fieldNames);
                }
            }
        });

        static::deleted(function (FlexyModelContract $flexyModelContract) {
            $modelType = static::getModelType();
            $modelId = $flexyModelContract->id;

            Value::where([
                'model_type' => $modelType,
                'model_id' => $modelId,
            ])->delete();
        });
    }

    // ==================== Field Set Management Methods ====================

    /**
     * Create a new field set for this model type
     *
     * @throws \Exception
     */
    public static function createFieldSet(
        string $setCode,
        string $label,
        ?string $description = null,
        ?array $metadata = null,
        bool $isDefault = false
    ): FieldSet {
        $modelType = static::getModelType();

        return FieldSet::create([
            'model_type' => $modelType,
            'set_code' => $setCode,
            'label' => $label,
            'description' => $description,
            'metadata' => $metadata,
            'is_default' => $isDefault,
        ]);
    }

    /**
     * Get a field set by set code for this model type
     */
    public static function getFieldSet(string $setCode): ?FieldSet
    {
        $modelType = static::getModelType();

        return FieldSet::where('model_type', $modelType)
            ->where('set_code', $setCode)
            ->first();
    }

    /**
     * Get all field sets for this model type
     */
    public static function getAllFieldSets(): Collection
    {
        $modelType = static::getModelType();

        return FieldSet::where('model_type', $modelType)
            ->orderBy('is_default', 'desc')
            ->orderBy('label')
            ->get();
    }

    /**
     * Delete a field set (with usage check)
     *
     * @throws FieldSetInUseException
     */
    public static function deleteFieldSet(string $setCode): bool
    {
        $modelType = static::getModelType();

        $fieldSet = FieldSet::where('model_type', $modelType)
            ->where('set_code', $setCode)
            ->first();

        if (! $fieldSet) {
            return false;
        }

        // Check if field set is in use
        $usageCount = $fieldSet->getUsageCount($modelType);
        if ($usageCount > 0) {
            throw FieldSetInUseException::cannotDelete($setCode, $usageCount);
        }

        return $fieldSet->delete();
    }

    // ==================== Field Management Methods ====================

    /**
     * Add a field to a field set
     */
    public static function addFieldToSet(
        string $setCode,
        string $fieldName,
        FlexyFieldType $fieldType,
        int $sort = 100,
        ?string $validationRules = null,
        ?array $validationMessages = null,
        ?array $fieldMetadata = null
    ): SetField {
        $setField = SetField::create([
            'set_code' => $setCode,
            'field_name' => $fieldName,
            'field_type' => $fieldType,
            'sort' => $sort,
            'validation_rules' => $validationRules,
            'validation_messages' => $validationMessages,
            'field_metadata' => $fieldMetadata,
        ]);

        // Recreate pivot view to include new field
        FlexyField::recreateViewIfNeeded([$fieldName]);

        return $setField;
    }

    /**
     * Remove a field from a field set
     */
    public static function removeFieldFromSet(string $setCode, string $fieldName): bool
    {
        return SetField::where('set_code', $setCode)
            ->where('field_name', $fieldName)
            ->delete();
    }

    /**
     * Get all fields for a field set
     */
    public static function getFieldsForSet(string $setCode): Collection
    {
        return SetField::where('set_code', $setCode)
            ->orderBy('sort')
            ->get();
    }

    // ==================== Instance Methods ====================

    /**
     * Assign this model instance to a field set
     *
     * @throws FieldSetNotFoundException
     */
    public function assignToFieldSet(string $setCode): void
    {
        $modelType = static::getModelType();

        // Verify field set exists
        $fieldSet = FieldSet::where('model_type', $modelType)
            ->where('set_code', $setCode)
            ->first();

        if (! $fieldSet) {
            throw FieldSetNotFoundException::forSetCode($setCode, $modelType);
        }

        // Update model's field_set_code
        $this->field_set_code = $setCode;
        $this->save();
    }

    /**
     * Get the field set code for this instance
     */
    public function getFieldSetCode(): ?string
    {
        return $this->field_set_code ?? null;
    }

    /**
     * Get available fields for this instance's field set
     */
    public function getAvailableFields(): Collection
    {
        $fieldSetCode = $this->getFieldSetCode();

        if (! $fieldSetCode) {
            return collect();
        }

        return SetField::where('set_code', $fieldSetCode)
            ->orderBy('sort')
            ->get();
    }

    /**
     * Get the field set relationship
     */
    public function fieldSet(): BelongsTo
    {
        return $this->belongsTo(FieldSet::class, 'field_set_code', 'set_code');
    }

    // ==================== Flexy Accessor ====================

    public function flexy(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->fields == null) {
                    $this->fields = new \AuroraWebSoftware\FlexyField\Models\Flexy;
                    $this->fields->_model_type = static::getModelType();
                    $this->fields->_model_id = $this->id;

                    $fieldSetCode = $this->getFieldSetCode();

                    // Query for values, optionally filtered by field_set_code
                    $valuesQuery = Value::where([
                        'ff_values.model_type' => static::getModelType(),
                        'ff_values.model_id' => $this->id,
                    ]);

                    if ($fieldSetCode) {
                        $valuesQuery->where('ff_values.field_set_code', $fieldSetCode);
                    }

                    $values = $valuesQuery
                        ->leftJoin('ff_set_fields', function ($join) use ($fieldSetCode) {
                            $join->on('ff_values.field_name', '=', 'ff_set_fields.field_name');
                            if ($fieldSetCode) {
                                $join->where('ff_set_fields.set_code', '=', $fieldSetCode);
                            }
                        })
                        ->orderBy('ff_set_fields.sort')
                        ->select('ff_values.*', 'ff_set_fields.sort')
                        ->get();

                    $values->each(function ($value) {
                        $fieldValue = $value->value_date ??
                            $value->value_datetime ??
                            $value->value_decimal ??
                            $value->value_int ??
                            $value->value_string ??
                            $value->value_boolean ?? null;

                        // Decode JSON if present (value_json is stored as JSON string in database)
                        if ($value->value_json !== null && $fieldValue === null) {
                            $fieldValue = json_decode($value->value_json, true);
                        }

                        $this->fields[$value->field_name] = $fieldValue;
                    });
                }

                $this->fields->setRawAttributes($this->fields->getAttributes(), true);

                return $this->fields;
            },
        );
    }

    // ==================== Query Scopes ====================

    /**
     * Scope to filter by field set code
     */
    public function scopeWhereFieldSet(Builder $query, string $setCode): Builder
    {
        return $query->where($this->getTable().'.field_set_code', $setCode);
    }

    /**
     * Scope to filter by multiple field set codes
     */
    public function scopeWhereFieldSetIn(Builder $query, array $setCodes): Builder
    {
        return $query->whereIn($this->getTable().'.field_set_code', $setCodes);
    }

    /**
     * Scope to filter models without field set assignment
     */
    public function scopeWhereFieldSetNull(Builder $query): Builder
    {
        return $query->whereNull($this->getTable().'.field_set_code');
    }
}
