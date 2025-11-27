<?php

namespace AuroraWebSoftware\FlexyField\Contracts;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

interface FlexyModelContract
{
    public static function getModelType(): string;

    // ==================== Field Set Management ====================

    /**
     * Create a new field set for this model type
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function createFieldSet(
        string $setCode,
        string $label,
        ?string $description = null,
        ?array $metadata = null,
        bool $isDefault = false
    ): FieldSet;

    /**
     * Get a field set by set code
     */
    public static function getFieldSet(string $setCode): ?FieldSet;

    /**
     * Get all field sets for this model type
     *
     * @return Collection<int, FieldSet>
     */
    public static function getAllFieldSets(): Collection;

    /**
     * Delete a field set
     */
    public static function deleteFieldSet(string $setCode): bool;

    // ==================== Field Management ====================

    /**
     * Add a field to a field set
     *
     * @param  array<string, string>|null  $validationMessages
     * @param  array<string, mixed>|null  $fieldMetadata
     */
    public static function addFieldToSet(
        string $setCode,
        string $fieldName,
        FlexyFieldType $fieldType,
        int $sort = 100,
        ?string $validationRules = null,
        ?array $validationMessages = null,
        ?array $fieldMetadata = null
    ): SetField;

    /**
     * Remove a field from a field set
     */
    public static function removeFieldFromSet(string $setCode, string $fieldName): bool;

    /**
     * Get all fields for a field set
     *
     * @return Collection<int, SetField>
     */
    public static function getFieldsForSet(string $setCode): Collection;

    // ==================== Instance Methods ====================

    /**
     * Assign this instance to a field set
     */
    public function assignToFieldSet(string $setCode): void;

    /**
     * Get the field set code for this instance
     */
    public function getFieldSetCode(): ?string;

    /**
     * Get available fields for this instance
     *
     * @return Collection<int, SetField>
     */
    public function getAvailableFields(): Collection;

    /**
     * Get the field set relationship
     *
     * @return BelongsTo<FieldSet, Model>
     */
    public function fieldSet(): BelongsTo;

    /**
     * Get the flexy fields accessor
     *
     * @return Attribute<\AuroraWebSoftware\FlexyField\Models\Flexy, never>
     */
    public function flexy(): Attribute;
}
