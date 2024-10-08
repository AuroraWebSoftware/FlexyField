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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

trait Flexy
{
    private ?\AuroraWebSoftware\FlexyField\Models\Flexy $fields = null;

    private ?array $creatingFields = null;

    public static bool $hasShape = false;

    public static function getModelType(): string
    {
        return static::class;
    }

    public static function hasShape(): bool
    {
        return self::$hasShape;
    }

    public static function bootFlexy(): void
    {
        static::addGlobalScope('flexy', function (Builder $builder) {
            $modelType = static::getModelType();
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

                $modelType = static::getModelType();
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

                    $addition = [
                        'value_string' => null,
                        'value_int' => null,
                        'value_decimal' => null,
                        'value_datetime' => null,
                        'value_boolean' => null,
                        'value_json' => null,
                    ];

                    if (is_string($value)) {
                        $addition['value_string'] = $value;
                    } elseif (is_int($value)) {
                        $addition['value_int'] = $value;
                    } elseif (is_numeric($value)) {
                        $addition['value_decimal'] = $value;
                    } elseif ($value instanceof DateTime) {
                        $addition['value_datetime'] = $value;
                    } elseif (is_bool($value)) {
                        $addition['value_boolean'] = $value;
                    } elseif (is_array($value)) {
                        $addition['value_json'] = json_encode($value);
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

        static::deleted(function (FlexyModelContract $flexyModelContract) {
            $modelType = static::getModelType();
            $modelId = $flexyModelContract->id;

            Value::where([
                'model_type' => $modelType,
                'model_id' => $modelId,
            ])->delete();

            FlexyField::dropAndCreatePivotView();
        });
    }

    public static function setFlexyShape(
        string $fieldName, FlexyFieldType $fieldType,
        int $sort,
        ?string $validationRules = null,
        ?array $validationMessages = null,
        ?array $fieldMetadata = []
    ): Shape {
        $modelType = static::getModelType();

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
        $modelType = static::getModelType();

        return Shape::where('model_type', $modelType)->where('field_name', $fieldName)->first();
    }

    public static function getAllFlexyShapes(): ?Collection
    {
        $modelType = static::getModelType();

        return Shape::where('model_type', $modelType)->orderBy('sort')->get();
    }

    public static function deleteFlexyShape(string $fieldName): bool
    {
        $modelType = static::getModelType();

        return Shape::where('model_type', $modelType)->where('field_name', $fieldName)->delete();
    }

    public function flexy(): Attribute
    {
        return new Attribute(
            get: function () {
                if ($this->fields == null) {

                    $this->fields = new \AuroraWebSoftware\FlexyField\Models\Flexy;
                    $this->fields->_model_type = static::getModelType();
                    $this->fields->_model_id = $this->id;

                    $values = Value::where([
                        'ff_values.model_type' => static::getModelType(),
                        'ff_values.model_id' => $this->id,
                    ])
                        ->leftJoin('ff_shapes', function ($join) {
                            $join->on('ff_values.field_name', '=', 'ff_shapes.field_name')
                                ->on('ff_values.model_type', '=', 'ff_shapes.model_type');
                        })
                        ->orderBy('ff_shapes.sort')
                        ->select('ff_values.*', 'ff_shapes.sort')
                        ->get();

                    $values->each(function ($value) {
                        $this->fields[$value->field_name] =
                            $value->value_date ??
                            $value->value_datetime ??
                            $value->value_decimal ??
                            $value->value_int ??
                            $value->value_string ??
                            $value->value_json ??
                            $value->value_boolean ?? null;
                    });
                }

                $this->fields->setRawAttributes($this->fields->getAttributes(), true);

                //$this->fields->syncOriginal();
                return $this->fields;
            },

        );
    }
}
