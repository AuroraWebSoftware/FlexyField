<?php

namespace AuroraWebSoftware\FlexyField\Contracts;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;

interface FlexyModelContract
{
    public static function setFlexyShape(string $fieldName, FlexyFieldType $fieldType, int $sort, ?string $validationRules = null, ?array $validationMessages = null): Shape;

    public static function getFlexyShape(string $fieldName): ?Shape;

    public static function deleteFlexyShape(string $fieldName): bool;
}
