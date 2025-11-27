<?php

use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\Value;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('field_set_code')->nullable();
        $table->timestamps();
    });
});

it('can create a value record', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default',
        'label' => 'Default',
    ]);

    $value = Value::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'field_name' => 'test_field',
        'value_string' => 'test_value',
        'field_set_code' => 'default',
    ]);

    expect($value)->toBeInstanceOf(Value::class)
        ->and($value->model_type)->toBe(ExampleFlexyModel::class)
        ->and($value->model_id)->toBe($model->id)
        ->and($value->field_name)->toBe('test_field')
        ->and($value->value_string)->toBe('test_value')
        ->and($value->field_set_code)->toBe('default');
});

it('casts value_boolean to boolean', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default',
        'label' => 'Default',
    ]);

    $value = Value::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'field_name' => 'is_active',
        'value_boolean' => true,
        'field_set_code' => 'default',
    ]);

    expect($value->value_boolean)->toBeTrue();

    $value->value_boolean = false;
    $value->save();

    expect($value->fresh()->value_boolean)->toBeFalse();
});

it('has fieldSet relationship', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default',
        'label' => 'Default',
    ]);

    $value = Value::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'field_name' => 'test_field',
        'value_string' => 'test_value',
        'field_set_code' => 'default',
    ]);

    expect($value->fieldSet)->toBeInstanceOf(FieldSet::class)
        ->and($value->fieldSet->set_code)->toBe('default');
});

it('does not have timestamps', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default',
        'label' => 'Default',
    ]);

    $value = Value::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'field_name' => 'test_field',
        'value_string' => 'test_value',
        'field_set_code' => 'default',
    ]);

    expect($value->created_at)->toBeNull()
        ->and($value->updated_at)->toBeNull();
});
