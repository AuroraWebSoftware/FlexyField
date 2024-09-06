<?php

namespace AuroraWebSoftware\FlexyField\Traits;

use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;
use AuroraWebSoftware\FlexyField\Models\Value;
use DateTime;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait Flexy
{
    private ?\AuroraWebSoftware\FlexyField\Models\Flexy $fields = null;

    private ?array $creatingFields = null;

    public static function bootFlexy(): void
    {
        static::saving(function (FlexyModelContract $flexyModelContract) {

            // todo validations shape e göre

            if ($flexyModelContract->flexy->isDirty()) {

                $modelType = static::class;
                $dirtyFields = $flexyModelContract->flexy->getDirty() ?? [];

                foreach ($dirtyFields as $field => $value) {

                    $addition = [];

                    if (is_string($value)) {
                        $addition['value_string'] = $value;
                    } elseif (is_int($value)) {
                        $addition['value_int'] = $value;
                    } elseif (is_numeric($value)) {
                        $addition['value_decimal'] = $value;
                    } elseif ($value instanceof DateTime) {
                        // todo
                    } elseif (DateTime::createFromFormat('Y-m-d', $value)) {
                        // todo
                    } elseif (DateTime::createFromFormat('Y-m-d H:i:s', $value)) {
                        // todo
                    } else {
                        // todo
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
                            ...$addition,
                        ]
                    );
                }
            }
        });
    }

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

    public function flexy(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->fields == null) {

                    $this->fields = new \AuroraWebSoftware\FlexyField\Models\Flexy;
                    $this->fields->_model_type = static::class;
                    $this->fields->_model_id = $this->id;

                    $values = Value::where(
                        [
                            'model_type' => static::class,
                            'model_id' => $this->id,
                        ]
                    )->get();

                    $values->each(function ($value) {
                        $this->fields[$value->field_name] =
                            $value->value_date ??
                            $value->value_datetime ??
                            $value->value_decimal ??
                            $value->value_int ??
                            $value->value_string ?? null;
                    });
                }
                $this->fields->a = 1;
                $this->fields->b = 2;

                $this->fields->setRawAttributes($this->fields->getAttributes(), true);

                //$this->fields->syncOriginal();
                return $this->fields;
            },

        );
    }
}
