<?php

namespace AuroraWebSoftware\FlexyField\Contracts;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\SchemaField;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

interface FlexyModelContract
{
    public static function getModelType(): string;

    // ==================== Schema Management ====================

    /**
     * Create a new schema for this model type
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function createSchema(
        string $schemaCode,
        string $label,
        ?string $description = null,
        ?array $metadata = null,
        bool $isDefault = false
    ): FieldSchema;

    /**
     * Get a schema by schema code
     */
    public static function getSchema(string $schemaCode): ?FieldSchema;

    /**
     * Get all schemas for this model type
     *
     * @return Collection<int, FieldSchema>
     */
    public static function getAllSchemas(): Collection;

    /**
     * Delete a schema
     */
    public static function deleteSchema(string $schemaCode): bool;

    // ==================== Field Management ====================

    /**
     * Add a field to a schema
     *
     * @param  array<string, string>|null  $validationMessages
     * @param  array<string, mixed>|null  $fieldMetadata
     */
    public static function addFieldToSchema(
        string $schemaCode,
        string $fieldName,
        FlexyFieldType $fieldType,
        int $sort = 100,
        ?string $validationRules = null,
        ?array $validationMessages = null,
        ?array $fieldMetadata = null
    ): SchemaField;

    /**
     * Remove a field from a schema
     */
    public static function removeFieldFromSchema(string $schemaCode, string $fieldName): bool;

    /**
     * Get all fields for a schema
     *
     * @return Collection<int, SchemaField>
     */
    public static function getFieldsForSchema(string $schemaCode): Collection;

    // ==================== Instance Methods ====================

    /**
     * Assign this instance to a schema
     */
    public function assignToSchema(string $schemaCode): void;

    /**
     * Get schema code for this instance
     */
    public function getSchemaCode(): ?string;

    /**
     * Get available fields for this instance
     *
     * @return Collection<int, SchemaField>
     */
    public function getAvailableFields(): Collection;

    /**
     * Get schema relationship
     *
     * @return BelongsTo<FieldSchema, Model>
     */
    public function schema(): BelongsTo;

    /**
     * Get flexy fields accessor
     *
     * @return Attribute<\auroraWebSoftware\FlexyField\Models\Flexy, never>
     */
    public function flexy(): Attribute;
}
