<?php

use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\FieldValue;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {

    Schema::dropIfExists('ff_example_flexy_models');
    Schema::create('ff_example_flexy_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable();
        $table->timestamps();
    });
});

it('can create a value record', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default',
        'label' => 'Default',
    ]);

    $value = FieldValue::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'test_value',
        'schema_code' => 'default',
    ]);

    expect($value)->toBeInstanceOf(FieldValue::class)
        ->and($value->model_type)->toBe(ExampleFlexyModel::class)
        ->and($value->model_id)->toBe($model->id)
        ->and($value->name)->toBe('test_field')
        ->and($value->value_string)->toBe('test_value')
        ->and($value->schema_code)->toBe('default');
});

it('casts value_boolean to boolean', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default',
        'label' => 'Default',
    ]);

    $value = FieldValue::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'is_active',
        'value_boolean' => true,
        'schema_code' => 'default',
    ]);

    expect($value->value_boolean)->toBeTrue();

    $value->value_boolean = false;
    $value->save();

    expect($value->fresh()->value_boolean)->toBeFalse();
});

it('has schema relationship', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default',
        'label' => 'Default',
    ]);

    $value = FieldValue::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'test_value',
        'schema_code' => 'default',
    ]);

    expect($value->schema)->toBeInstanceOf(FieldSchema::class)
        ->and($value->schema->schema_code)->toBe('default');
});

it('does not have timestamps', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default',
        'label' => 'Default',
    ]);

    $value = FieldValue::create([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'test_value',
        'schema_code' => 'default',
    ]);

    expect($value->created_at)->toBeNull()
        ->and($value->updated_at)->toBeNull();
});
