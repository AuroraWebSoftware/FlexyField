<?php

namespace AuroraWebSoftware\FlexyField\Contracts;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;
use Illuminate\Database\Eloquent\Casts\Attribute;

interface FlexyModelContract
{

    public static function getModelType(): string;

    public static function hasShape(): bool;

    /**
     * @param  array<string, string>|null  $validationMessages
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
