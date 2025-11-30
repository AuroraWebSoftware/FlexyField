<?php

namespace AuroraWebSoftware\FlexyField\Models;

use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSchemaException;
use Illuminate\Database\Eloquent\Model;

class Flexy extends Model
{
    protected $guarded = [];

    public ?string $_model_type = null;

    public ?int $_model_id = null;

    public ?string $_schema_code = null;

    /**
     * Get a property value, validating it exists in the schema
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws FieldNotInSchemaException
     */
    public function __get($key)
    {
        // Allow access to internal properties
        if (str_starts_with($key, '_')) {
            return parent::__get($key);
        }

        // Check if attribute exists in model (from parent __get)
        if (isset($this->attributes[$key]) || array_key_exists($key, $this->attributes)) {
            return parent::__get($key);
        }

        // If we have schema code, validate the field exists in schema
        if ($this->_schema_code && $this->_model_type) {
            $schemaField = SchemaField::where('schema_code', $this->_schema_code)
                ->where('name', $key)
                ->first();

            if (! $schemaField) {
                $availableFields = SchemaField::where('schema_code', $this->_schema_code)
                    ->pluck('name')
                    ->toArray();
                throw FieldNotInSchemaException::forField($key, $this->_schema_code, $availableFields);
            }
        }

        // Return null if field doesn't exist (for backward compatibility)
        return parent::__get($key);
    }

    /**
     * Set a property value, validating it exists in the schema
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     *
     * @throws FieldNotInSchemaException
     * @throws \AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException
     */
    public function __set($key, $value)
    {
        // Allow access to internal properties
        if (str_starts_with($key, '_')) {
            parent::__set($key, $value);

            return;
        }

        // Check if attribute exists in model (from parent __set)
        if (isset($this->attributes[$key]) || array_key_exists($key, $this->attributes)) {
            parent::__set($key, $value);

            return;
        }

        // If we don't have a schema code, we can't validate, but we should probably prevent assignment
        // unless it's a standard model attribute.
        // However, the requirement is to throw SchemaNotFoundException if no schema is assigned.
        if (! $this->_schema_code) {
            // If model_type is set, we can throw SchemaNotFoundException
            // But only if model is already saved (has an ID), otherwise allow setting for unsaved models
            // Unsaved models will be validated on save
            if ($this->_model_type && $this->_model_id && $this->_model_id > 0) {
                throw \AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException::notAssigned($this->_model_type, $this->_model_id);
            }
            // For unsaved models without schema, allow setting but it will fail validation on save
            parent::__set($key, $value);

            return;
        }

        // Validate the field exists in schema
        $schemaField = SchemaField::where('schema_code', $this->_schema_code)
            ->where('name', $key)
            ->first();

        if (! $schemaField) {
            $availableFields = SchemaField::where('schema_code', $this->_schema_code)
                ->pluck('name')
                ->toArray();
            throw FieldNotInSchemaException::forField($key, $this->_schema_code, $availableFields);
        }

        parent::__set($key, $value);
    }
}
