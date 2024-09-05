<?php

namespace AuroraWebSoftware\FlexyField\Traits;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;

trait Flexy
{
    public static function setFlexyShape(
        string $fieldName, FlexyFieldType $fieldType,
        int $sort,
        ?string $validationRules = null, ?array $validationMessages = null
    ): Shape {
        $modelType = static::class;

        return Shape::updateOrCreate(
            ['model_type' => $modelType, 'field_name' => $fieldName],
            [
                'model_type' => $modelType, 'field_name' => $fieldName,
                'field_type' => $fieldType, 'sort' => $sort,
                'validation_rules' => $validationRules, 'validation_messages' => $validationMessages,
            ]
        );
    }

    public static function getFlexyShape(string $fieldName): ?Shape
    {
        $modelType = static::class;

        return Shape::where('model_type', $modelType)->where('field_name', $fieldName)->first();
    }

    public static function deleteFlexyShape(string $fieldName): bool
    {
        $modelType = static::class;

        return Shape::where('model_type', $modelType)->where('field_name', $fieldName)->delete();
    }
}
