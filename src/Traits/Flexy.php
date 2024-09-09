<?php

namespace AuroraWebSoftware\FlexyField\Traits;

use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldIsNotInShape;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldTypeNotAllowedException;
use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Models\Shape;
use AuroraWebSoftware\FlexyField\Models\Value;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Validator;

trait Flexy
{
    private ?\AuroraWebSoftware\FlexyField\Models\Flexy $fields = null;

    private ?array $creatingFields = null;

    public static bool $hasShape = false;

    public static function hasShape(): bool
    {
        return self::$hasShape;
    }

    public static function bootFlexy(): void
    {
        static::addGlobalScope('flexy', function (Builder $builder) {
            $modelType = static::class;
            $builder->leftJoin('ff_values_pivot_view', 'ff_values_pivot_view.model_id', '=', 'id')
                ->where(function ($query) use ($modelType) {
                    $query->where('ff_values_pivot_view.model_type', '=', $modelType)
                        ->orWhereNull('ff_values_pivot_view.model_type');
                });
        });

        static::saving(/**
         * @throws FlexyFieldTypeNotAllowedException
         * @throws \Exception
         */ function (FlexyModelContract $flexyModelContract) {

            if ($flexyModelContract->flexy->isDirty()) {

                $modelType = static::class;
                $dirtyFields = $flexyModelContract->flexy->getDirty() ?? [];

                foreach ($dirtyFields as $field => $value) {

                    if ($flexyModelContract::hasShape()) {
                        $shape = Shape::where('model_type', $modelType)
                            ->where('field_name', $field)
                            ->first() ?? throw new FlexyFieldIsNotInShape($field);

                        $data = [$field => $value];
                        $rules = $shape?->validation_rules ? [$field => $shape->validation_rules] : [];
                        $messages = $shape?->validation_rule ? [$field => $shape->validation_rules] : [];

                        Validator::make($data, $rules, $messages)->validate();
                    }

                    $addition = [];

                    if (is_string($value)) {
                        $addition['value_string'] = $value;
                    } elseif (is_int($value)) {
                        $addition['value_int'] = $value;
                    } elseif (is_numeric($value)) {
                        $addition['value_decimal'] = $value;
                    } elseif ($value instanceof DateTime) {
                        $addition['value_datetime'] = $value;
                    } else {
                        throw new FlexyFieldTypeNotAllowedException;
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

                if ($dirtyFields) {
                    $flexyModelContract->flexy->setRawAttributes($flexyModelContract->flexy->getAttributes(), true);
                    FlexyField::dropAndCreatePivotView();
                }
            }

        });
    }

    public static function setFlexyShape(
        string $fieldName, FlexyFieldType $fieldType,
        int $sort,
        ?string $validationRules = null,
        ?array $validationMessages = null,
        ?array $fieldMetadata = []
    ): Shape {
        $modelType = static::class;

        return Shape::updateOrCreate(
            ['model_type' => $modelType, 'field_name' => $fieldName],
            [
                'model_type' => $modelType, 'field_name' => $fieldName,
                'field_type' => $fieldType, 'sort' => $sort,
                'validation_rules' => $validationRules,
                'validation_messages' => $validationMessages,
                'field_metadata' => $fieldMetadata,
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

                $this->fields->setRawAttributes($this->fields->getAttributes(), true);

                //$this->fields->syncOriginal();
                return $this->fields;
            },

        );
    }
}
