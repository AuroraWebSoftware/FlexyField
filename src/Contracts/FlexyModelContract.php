<?php

namespace AuroraWebSoftware\FlexyField\Contracts;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;
use Illuminate\Database\Eloquent\Casts\Attribute;

interface FlexyModelContract
{
    public static function hasShape(): bool;

    /**
     * @param string $fieldName
     * @param FlexyFieldType $fieldType
     * @param int $sort
     * @param string|null $validationRules
     * @param array<string, string>|null $validationMessages
     * @return Shape
     */
    public static function setFlexyShape(string $fieldName, FlexyFieldType $fieldType,
        int $sort, ?string $validationRules = null,
        ?array $validationMessages = null
    ): Shape;

    public static function getFlexyShape(string $fieldName): ?Shape;

    public static function deleteFlexyShape(string $fieldName): bool;

    /**
     * @return Attribute<string, string>
     */
    public function flexy(): Attribute;
}
