<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(CreatesSchemas::class);

beforeEach(function () {

    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable();
        $table->timestamps();
    });
});

it('creates field set with fields', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);

    $this->assertDatabaseHas('ff_schemas', [
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default',
        'label' => 'Default',
    ]);

    $this->assertDatabaseHas('ff_schema_fields', [
        'schema_code' => 'default',
        'name' => 'test_field',
        'type' => FlexyFieldType::STRING->value,
    ]);

    $this->assertDatabaseHas('ff_schema_fields', [
        'schema_code' => 'default',
        'name' => 'count',
        'type' => FlexyFieldType::INTEGER->value,
    ]);

    $this->assertDatabaseHas('ff_schema_fields', [
        'schema_code' => 'default',
        'name' => 'price',
        'type' => FlexyFieldType::DECIMAL->value,
    ]);

    $this->assertDatabaseHas('ff_schema_fields', [
        'schema_code' => 'default',
        'name' => 'is_active',
        'type' => FlexyFieldType::BOOLEAN->value,
    ]);
});

it('creates model with flexy fields', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);
    $model = ExampleFlexyModel::create(['name' => 'Test Product']);

    $model->assignToSchema('default');
    $model->flexy->test_field = 'test value';
    $model->flexy->count = 42;
    $model->flexy->price = 19.99;
    $model->flexy->is_active = true;
    $model->save();

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'test value',
        'schema_code' => 'default',
    ]);

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'count',
        'value_int' => 42,
        'schema_code' => 'default',
    ]);

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'price',
        'value_decimal' => 19.99,
        'schema_code' => 'default',
    ]);

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'is_active',
        'value_boolean' => true,
        'schema_code' => 'default',
    ]);
});

it('validates field values', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'default',
        fields: [
            'test_field' => ['type' => FlexyFieldType::STRING],
            'count' => [
                'type' => FlexyFieldType::INTEGER,
                'rules' => 'integer',
            ],
        ],
        isDefault: true
    );

    $model = ExampleFlexyModel::create(['name' => 'Test Product']);
    $model->assignToSchema('default');

    // Valid data should save
    $model->flexy->test_field = 'valid';
    $model->flexy->count = 42;
    $model->save();

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'valid',
    ]);

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'count',
        'value_int' => 42,
    ]);

    // Invalid data should throw validation exception
    $invalidModel = ExampleFlexyModel::create(['name' => 'Invalid Product']);
    $invalidModel->assignToSchema('default');

    $this->expectException(ValidationException::class);
    $invalidModel->flexy->count = 'not a number';
    $invalidModel->save();
});

it('retrieves flexy fields correctly', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);
    $model = ExampleFlexyModel::create(['name' => 'Test Product']);
    $model->assignToSchema('default');

    $model->flexy->test_field = 'test value';
    $model->flexy->count = 42;
    $model->flexy->price = 19.99;
    $model->flexy->is_active = true;
    $model->save();

    // Refresh model to ensure clean state
    $model->refresh();

    expect($model->flexy->test_field)->toBe('test value');
    expect($model->flexy->count)->toBe(42);
    expect($model->flexy->price)->toBe(19.99);
    expect($model->flexy->is_active)->toBeTrue();
});

it('handles json fields', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);
    // Add json field to the default schema - ALREADY ADDED IN createDefaultSchema
    // ExampleFlexyModel::addFieldToSchema('default', 'json_field', FlexyFieldType::JSON);

    $model = ExampleFlexyModel::create(['name' => 'Test Product']);
    $model->assignToSchema('default');

    $testArray = ['key1' => 'value1', 'key2' => 'value2'];
    $model->flexy->json_field = $testArray;
    $model->save();

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'json_field',
        'schema_code' => 'default',
    ]);

    // Refresh model to ensure clean state
    $model->refresh();

    $retrievedArray = $model->flexy->json_field;
    expect($retrievedArray)->toEqual($testArray);
});

it('updates existing field values', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);
    $model = ExampleFlexyModel::create(['name' => 'Test Product']);
    $model->assignToSchema('default');

    // Create initial value
    $model->flexy->test_field = 'initial value';
    $model->save();

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'initial value',
        'schema_code' => 'default',
    ]);

    // Update the value
    $model->flexy->test_field = 'updated value';
    $model->save();

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'updated value',
        'schema_code' => 'default',
    ]);
});

it('deletes field values when model is deleted', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);
    $model = ExampleFlexyModel::create(['name' => 'Test Product']);
    $model->assignToSchema('default');

    $model->flexy->test_field = 'test value';
    $model->flexy->count = 42;
    $model->save();

    // Verify values exist
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
        'value_string' => 'test value',
        'schema_code' => 'default',
    ]);

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'count',
        'value_int' => 42,
        'schema_code' => 'default',
    ]);

    // Delete the model
    $model->delete();

    // Verify values are deleted
    $this->assertDatabaseMissing('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'test_field',
    ]);

    $this->assertDatabaseMissing('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'count',
    ]);
});

it('creates pivot view correctly', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);

    // Check that the view was created
    $this->assertTrue(
        Schema::hasView('ff_values_pivot_view'),
        'Pivot view should be created'
    );
});

it('handles date and datetime fields', function () {
    $this->createDefaultSchema(ExampleFlexyModel::class);
    // Add date and datetime fields to the default schema - ALREADY ADDED IN createDefaultSchema
    // ExampleFlexyModel::addFieldToSchema('default', 'date_field', FlexyFieldType::DATE);
    // ExampleFlexyModel::addFieldToSchema('default', 'datetime_field', FlexyFieldType::DATETIME);

    $model = ExampleFlexyModel::create(['name' => 'Test Product']);
    $model->assignToSchema('default');

    $date = now()->startOfDay();
    $datetime = now();

    $model->flexy->date_field = $date;
    $model->flexy->datetime_field = $datetime;
    $model->save();

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'date_field',
        'value_date' => $date->format('Y-m-d'),
        'schema_code' => 'default',
    ]);

    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'datetime_field',
        'value_datetime' => $datetime->format('Y-m-d H:i:s'),
        'schema_code' => 'default',
    ]);

    // Refresh model to ensure clean state
    $model->refresh();

    expect($model->flexy->date_field->format('Y-m-d'))->toBe($date->format('Y-m-d'));
    expect($model->flexy->datetime_field->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});
