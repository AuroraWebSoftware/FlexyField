<?php

namespace AuroraWebSoftware\FlexyField\Traits;

use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSchemaException;
use AuroraWebSoftware\FlexyField\Exceptions\SchemaInUseException;
use AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldTypeNotAllowedException;
use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\SchemaField;
use AuroraWebSoftware\FlexyField\Models\FieldValue;
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

        static::retrieved(function (FlexyModelContract $flexyModelContract) {
            $flexyModelContract->resetFlexy();
        });

        // Auto-assign default schema on model creation
        static::creating(function (FlexyModelContract $flexyModelContract) {
            if (! $flexyModelContract->schema_code) {
                $modelType = static::getModelType();
                $defaultSchema = FieldSchema::where('model_type', $modelType)
                    ->where('is_default', true)
                    ->first();

                if ($defaultSchema) {
                    $flexyModelContract->schema_code = $defaultSchema->schema_code;
                }
            }
        });

        static::saving(function (FlexyModelContract $flexyModelContract) {
            if ($flexyModelContract->flexy->isDirty()) {
                $modelType = static::getModelType();
                $dirtyFields = $flexyModelContract->flexy->getDirty() ?? [];

                // Get schema code for this instance
                $schemaCode = $flexyModelContract->getSchemaCode();

                if (! $schemaCode) {
                    throw SchemaNotFoundException::notAssigned($modelType, $flexyModelContract->id ?? 0);
                }

                // Get available fields for this schema
                $schemaFields = SchemaField::where('schema_code', $schemaCode)->get()->keyBy('name');

                // Prepare data for validation (all current values + dirty values)
                $validationData = $flexyModelContract->flexy->getAttributes();
                
                foreach ($dirtyFields as $field => $value) {
                    // Check if field exists in assigned schema
                    if (! $schemaFields->has($field)) {
                        $availableFields = $schemaFields->pluck('name')->toArray();
                        throw FieldNotInSchemaException::forField($field, $schemaCode, $availableFields);
                    }

                    $schemaField = $schemaFields[$field];

                    // Validate field value
                    if ($schemaField->validation_rules) {
                        $validationRules = $schemaField->getValidationRulesArray();
                        $rules = [$field => $validationRules];
                        $messages = $schemaField->validation_messages ? [$field => $schemaField->validation_messages] : [];

                        // Use the full validation data
                        Validator::make($validationData, $rules, $messages)->validate();
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

                    // Assign value based on schema field type
                    if ($schemaField->type === FlexyFieldType::DATE) {
                        $addition['value_date'] = $value;
                    } elseif ($schemaField->type === FlexyFieldType::DATETIME) {
                        $addition['value_datetime'] = $value;
                    } elseif ($schemaField->type === FlexyFieldType::BOOLEAN) {
                         // Handle boolean casting explicitly, including empty strings
                         if ($value === null) {
                             $addition['value_boolean'] = null;
                         } else {
                             $addition['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                         }
                    } elseif ($schemaField->type === FlexyFieldType::INTEGER) {
                        $addition['value_int'] = $value !== null ? (int) $value : null;
                    } elseif ($schemaField->type === FlexyFieldType::DECIMAL) {
                        $addition['value_decimal'] = $value !== null ? (float) $value : null;
                    } elseif ($schemaField->type === FlexyFieldType::JSON) {
                        $addition['value_json'] = $value !== null ? json_encode($value) : null;
                    } else {
                        // Default to string for STRING type or unknown
                        $addition['value_string'] = $value !== null ? (string) $value : null;
                    }

                    // Get schema_id from schema_code for foreign key
                    $schema = FieldSchema::where('model_type', $modelType)
                        ->where('schema_code', $schemaCode)
                        ->first();

                    FieldValue::updateOrCreate(
                        [
                            'model_type' => $modelType,
                            'model_id' => $flexyModelContract->id,
                            'name' => $field,
                        ],
                        [
                            'model_type' => $modelType,
                            'model_id' => $flexyModelContract->id,
                            'name' => $field,
                            'schema_code' => $schemaCode,
                            'schema_id' => $schema?->id,
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

            FieldValue::where([
                'model_type' => $modelType,
                'model_id' => $modelId,
            ])->delete();
        });
    }

    // ==================== Schema Management Methods ====================

    /**
     * Create a new schema for this model type
     *
     * @throws \Exception
     */
    public static function createSchema(
        string $schemaCode,
        string $label,
        ?string $description = null,
        ?array $metadata = null,
        bool $isDefault = false
    ): FieldSchema {
        $modelType = static::getModelType();

        return FieldSchema::create([
            'model_type' => $modelType,
            'schema_code' => $schemaCode,
            'label' => $label,
            'description' => $description,
            'metadata' => $metadata,
            'is_default' => $isDefault,
        ]);
    }

    /**
     * Get a schema by schema code for this model type
     */
    public static function getSchema(string $schemaCode): ?FieldSchema
    {
        $modelType = static::getModelType();

        return FieldSchema::where('model_type', $modelType)
            ->where('schema_code', $schemaCode)
            ->first();
    }

    /**
     * Get all schemas for this model type
     */
    public static function getAllSchemas(): Collection
    {
        $modelType = static::getModelType();

        return FieldSchema::where('model_type', $modelType)
            ->orderBy('is_default', 'desc')
            ->orderBy('label')
            ->get();
    }

    /**
     * Delete a schema (with usage check)
     *
     * @throws SchemaInUseException
     */
    public static function deleteSchema(string $schemaCode): bool
    {
        $modelType = static::getModelType();

        $schema = FieldSchema::where('model_type', $modelType)
            ->where('schema_code', $schemaCode)
            ->first();

        if (! $schema) {
            return false;
        }

        // Check if schema is in use
        $usageCount = $schema->getUsageCount($modelType);
        if ($usageCount > 0) {
            throw SchemaInUseException::cannotDelete($schemaCode, $usageCount);
        }

        return $schema->delete();
    }

    // ==================== Field Management Methods ====================

    /**
     * Add a field to a schema
     */
    public static function addFieldToSchema(
        string $schemaCode,
        string $fieldName,
        FlexyFieldType $fieldType,
        int $sort = 100,
        ?string $validationRules = null,
        ?array $validationMessages = null,
        ?array $fieldMetadata = null
    ): SchemaField {
        $modelType = static::getModelType();
        
        // Get schema_id from schema_code for foreign key
        $schema = FieldSchema::where('model_type', $modelType)
            ->where('schema_code', $schemaCode)
            ->first();

        if (! $schema) {
            throw SchemaNotFoundException::forSchemaCode($schemaCode, $modelType);
        }

        $schemaField = SchemaField::create([
            'schema_code' => $schemaCode,
            'schema_id' => $schema->id,
            'name' => $fieldName,
            'type' => $fieldType,
            'sort' => $sort,
            'validation_rules' => $validationRules,
            'validation_messages' => $validationMessages,
            'metadata' => $fieldMetadata,
        ]);

        // Recreate pivot view to include new field
        FlexyField::recreateViewIfNeeded([$fieldName]);

        return $schemaField;
    }

    /**
     * Remove a field from a schema
     */
    public static function removeFieldFromSchema(string $schemaCode, string $fieldName): bool
    {
        return SchemaField::where('schema_code', $schemaCode)
            ->where('name', $fieldName)
            ->delete();
    }

    /**
     * Get all fields for a schema
     */
    public static function getFieldsForSchema(string $schemaCode): Collection
    {
        return SchemaField::where('schema_code', $schemaCode)
            ->orderBy('sort')
            ->get();
    }

    // ==================== Instance Methods ====================

    /**
     * Assign this model instance to a schema
     *
     * @throws SchemaNotFoundException
     */
    public function assignToSchema(string $schemaCode): void
    {
        $modelType = static::getModelType();

        // Verify schema exists
        $schema = FieldSchema::where('model_type', $modelType)
            ->where('schema_code', $schemaCode)
            ->first();

        if (! $schema) {
            throw SchemaNotFoundException::forSchemaCode($schemaCode, $modelType);
        }

        // Update model's schema_code
        $this->schema_code = $schemaCode;
        $this->save();

        // Update internal Flexy model if initialized
        if ($this->fields) {
            $this->fields->_schema_code = $schemaCode;
        }
    }

    /**
     * Get the schema code for this instance
     */
    public function getSchemaCode(): ?string
    {
        return $this->schema_code ?? null;
    }

    /**
     * Get available fields for this instance's schema
     */
    public function getAvailableFields(): Collection
    {
        $schemaCode = $this->getSchemaCode();

        if (! $schemaCode) {
            return collect();
        }

        return SchemaField::where('schema_code', $schemaCode)
            ->orderBy('sort')
            ->get();
    }

    /**
     * Get the schema relationship
     */
    public function schema(): BelongsTo
    {
        return $this->belongsTo(FieldSchema::class, 'schema_code', 'schema_code');
    }

    // ==================== Flexy Accessor ====================

    public function resetFlexy(): void
    {
        $this->fields = null;
    }

    public function flexy(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if ($this->fields == null) {
                    $schemaCode = $this->getSchemaCode();
                    
                    $this->fields = new \AuroraWebSoftware\FlexyField\Models\Flexy;
                    $this->fields->_model_type = static::getModelType();
                    $this->fields->_model_id = $attributes['id'] ?? $this->getKey();
                    $this->fields->_schema_code = $schemaCode;
                    
                    // Query for values, optionally filtered by schema_code
                    $valuesQuery = FieldValue::where([
                        'ff_field_values.model_type' => static::getModelType(),
                        'ff_field_values.model_id' => $attributes['id'] ?? $this->getKey(),
                    ]);

                    if ($schemaCode) {
                        $valuesQuery->where('ff_field_values.schema_code', $schemaCode);
                    }

                    $values = $valuesQuery
                        ->join('ff_schema_fields', function ($join) use ($schemaCode) {
                            $join->on('ff_field_values.name', '=', 'ff_schema_fields.name');
                            if ($schemaCode) {
                                $join->where('ff_schema_fields.schema_code', '=', $schemaCode);
                            }
                        })
                        ->orderBy('ff_schema_fields.sort')
                        ->select('ff_field_values.*', 'ff_schema_fields.sort', 'ff_schema_fields.type')
                        ->get();

                    $values->each(function ($value) {
                        // Initialize fieldValue from specific columns based on type or fallback
                        $fieldValue = null;
                        
                        // Cast based on schema field type
                        // Note: $value->type can be either an enum value or a string from the database
                        $typeValue = is_string($value->type) ? strtolower($value->type) : $value->type->value;
                        
                        if ($typeValue === FlexyFieldType::DATE->value) {
                            $fieldValue = $value->value_date ? \Carbon\Carbon::parse($value->value_date) : null;
                        } elseif ($typeValue === FlexyFieldType::DATETIME->value) {
                             $fieldValue = $value->value_datetime ? \Carbon\Carbon::parse($value->value_datetime) : null;
                        } elseif ($typeValue === FlexyFieldType::DECIMAL->value) {
                            $fieldValue = $value->value_decimal !== null ? (float) $value->value_decimal : null;
                        } elseif ($typeValue === FlexyFieldType::INTEGER->value) {
                            $fieldValue = $value->value_int !== null ? (int) $value->value_int : null;
                        } elseif ($typeValue === FlexyFieldType::BOOLEAN->value) {
                            $fieldValue = $value->value_boolean !== null ? (bool) $value->value_boolean : null;
                        } else {
                            // String or default
                            $fieldValue = $value->value_string;
                        }
                        
                        // Decode JSON if present (value_json is stored as JSON string in database)
                        if ($value->value_json !== null && $fieldValue === null) {
                            $fieldValue = json_decode($value->value_json, true);
                        }

                        $this->fields->setAttribute($value->name, $fieldValue);
                    });
                    
                    // Ensure schema code is synced (in case it was initialized before schema assignment)
                    if ($this->fields && $this->fields->_schema_code !== $this->getSchemaCode()) {
                        $this->fields->_schema_code = $this->getSchemaCode();
                    }

                    $this->fields->setRawAttributes($this->fields->getAttributes(), true);
                }

                return $this->fields;
            }
        );
    }

    // ==================== Query Scopes ====================

    /**
     * Scope to filter by schema code
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWhereSchema(Builder $query, string $schemaCode): Builder
    {
        return $query->where('schema_code', $schemaCode);
    }

    /**
     * Scope to filter by schema codes
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @param  array<string>  $schemaCodes
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWhereInSchema(Builder $query, array $schemaCodes): Builder
    {
        return $query->whereIn('schema_code', $schemaCodes);
    }

    /**
     * Scope to filter by default schema
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWhereDefaultSchema(Builder $query): Builder
    {
        $modelType = static::getModelType();
        
        return $query->whereHas('schema', function ($query) use ($modelType) {
            $query->where('model_type', $modelType)
                  ->where('is_default', true);
        });
    }

    /**
     * Scope to filter by having any schema assigned
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWhereHasSchema(Builder $query): Builder
    {
        return $query->whereNotNull('schema_code');
    }

    /**
     * Scope to filter by having no schema assigned
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeWhereDoesntHaveSchema(Builder $query): Builder
    {
        return $query->whereNull('schema_code');
    }

    /**
     * Override refresh to clear cached fields
     */
    public function refresh()
    {
        $this->fields = null;
        return parent::refresh();
    }

    /**
     * Get the model's attributes, excluding the flexy accessor
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();
        unset($attributes['flexy']);
        return $attributes;
    }

    /**
     * Get the attributes that have been changed, excluding the flexy accessor
     */
    public function getDirty()
    {
        $dirty = parent::getDirty();
        unset($dirty['flexy']);
        return $dirty;
    }
}
