<?php

namespace AuroraWebSoftware\FlexyField\Traits;

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;
use AuroraWebSoftware\FlexyField\Models\Value;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

trait Flexy
{
    private ?Collection $fields = null;

    public static function setFlexyShape(
        string  $fieldName, FlexyFieldType $fieldType,
        int     $sort,
        ?string $validationRules = null, ?array $validationMessages = null
    ): Shape
    {
        $modelType = static::class;
        return Shape::updateOrCreate(
            ['model_type' => $modelType, 'field_name' => $fieldName],
            [
                'model_type' => $modelType, 'field_name' => $fieldName,
                'field_type' => $fieldType, 'sort' => $sort,
                'validation_rules' => $validationRules, 'validation_messages' => $validationMessages
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

    public function flexy(): Attribute
    {
        return new Attribute(
            get: function () {

                if ($this->fields == null) {



                    $this->fields = Value::where(
                        [
                            'model_type' => static::class,

                        ]
                       );
                }



            }
        );
    }

}
