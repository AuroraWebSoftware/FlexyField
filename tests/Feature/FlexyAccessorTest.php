<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSchemaException;
use AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(CreatesSchemas::class);

beforeEach(function () {

    Schema::dropIfExists('ff_example_flexy_models');
    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable()->index();
        $table->timestamps();
    });
});

it('provides access to flexy fields', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set some flexy field values
    $model->flexy->field1 = 'test value';
    $model->flexy->field2 = 42;
    $model->save();

    // Check that flexy attribute is available
    expect($model->flexy)->toBeInstanceOf(\AuroraWebSoftware\FlexyField\Models\Flexy::class);
});

it('retrieves flexy fields correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set some flexy field values
    $model->flexy->field1 = 'test value';
    $model->flexy->field2 = 42;
    $model->save();

    // Refresh model to ensure clean state
    $model->refresh();

    // Check that flexy fields are retrieved correctly
    expect($model->flexy->field1)->toBe('test value');
    expect($model->flexy->field2)->toBe(42);
});

it('sets flexy fields correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set flexy field values
    $model->flexy->field1 = 'new value 1';
    $model->flexy->field2 = 100;
    $model->save();

    // Refresh model to ensure values are saved
    $model->refresh();

    // Check that values were saved
    expect($model->flexy->field1)->toBe('new value 1');
    expect($model->flexy->field2)->toBe(100);
});

it('handles dirty tracking correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set a flexy field value
    $model->flexy->field1 = 'initial value';
    $model->save();

    // Refresh model to ensure clean state
    $model->refresh();

    // Check that field is dirty
    expect($model->flexy->isDirty('field1'))->toBeFalse();

    // Set the same value (should not be dirty)
    $model->flexy->field1 = 'initial value';
    $model->save();

    // Refresh model
    $model->refresh();

    // Check that field is not dirty
    expect($model->flexy->isDirty('field1'))->toBeFalse();
});

it('handles null values correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set null flexy field value
    $model->flexy->field1 = null;
    $model->save();

    // Refresh model to ensure clean state
    $model->refresh();

    // Check that null value is retrieved
    expect($model->flexy->field1)->toBeNull();
});

it('handles type casting correctly', function () {
    // Create a schema with different field types
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
            'int_field' => ['type' => FlexyFieldType::INTEGER],
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
            'date_field' => ['type' => FlexyFieldType::DATE],
            'datetime_field' => ['type' => FlexyFieldType::DATETIME],
            'json_field' => ['type' => FlexyFieldType::JSON],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set values of different types
    $model->flexy->string_field = 'test string';
    $model->flexy->int_field = 42;
    $model->flexy->decimal_field = 19.99;
    $model->flexy->bool_field = true;
    $model->flexy->date_field = now()->startOfDay();
    $model->flexy->datetime_field = now();
    $model->flexy->json_field = ['key' => 'value'];
    $model->save();

    // Refresh model to ensure values are saved
    $model->refresh();

    // Check that values are cast correctly
    expect($model->flexy->string_field)->toBeString();
    expect($model->flexy->int_field)->toBeInt();
    expect($model->flexy->decimal_field)->toBeFloat();
    expect($model->flexy->bool_field)->toBeBool();
    expect($model->flexy->date_field)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($model->flexy->datetime_field)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($model->flexy->json_field)->toBeArray();
});

it('throws exception when setting field not in schema', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Try to set a field not in the schema
    $this->expectException(FieldNotInSchemaException::class);
    $model->flexy->non_existent_field = 'value';
    $model->save();
});

it('throws exception when setting field without schema assignment', function () {
    // Create a model without schema assignment
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Try to set a field
    $this->expectException(SchemaNotFoundException::class);
    $model->flexy->field1 = 'value';
    $model->save();
});

it('handles concurrent access correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create multiple models
    $models = [];
    for ($i = 0; $i < 5; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Test {$i}"]);
        $model->assignToSchema('test');
        $model->flexy->field1 = "value {$i}";
        $model->save();
        $models[] = $model;
    }

    // Access flexy fields concurrently
    foreach ($models as $model) {
        $model->refresh();
        $index = (int) str_replace('Test ', '', $model->name);
        expect($model->flexy->field1)->toBe('value '.$index);
    }
});

it('handles model deletion correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model with flexy fields
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'test value';
    $model->save();

    // Verify values exist
    expect(DB::table('ff_field_values')
        ->where('model_type', ExampleFlexyModel::class)
        ->where('schema_code', 'test')
        ->count())->toBe(1);

    // Delete the model
    $model->delete();

    // Check that values are deleted
    expect(DB::table('ff_field_values')
        ->where('model_type', ExampleFlexyModel::class)
        ->where('schema_code', 'test')
        ->count())->toBe(0);
});

it('handles schema changes correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set a flexy field value
    $model->flexy->field1 = 'initial value';
    $model->save();

    // Verify value exists with original schema
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'field1',
        'value_string' => 'initial value',
        'schema_code' => 'test',
    ]);

    // Change the schema
    $schema = ExampleFlexyModel::getSchema('test');
    $schema->label = 'Updated Schema';
    $schema->save();

    // Verify existing values still reference the old schema code
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'field1',
        'value_string' => 'initial value',
        'schema_code' => 'test',
    ]);
});

it('handles field value updates correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set initial value
    $model->flexy->field1 = 'initial value';
    $model->save();

    // Verify value exists
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'field1',
        'value_string' => 'initial value',
        'schema_code' => 'test',
    ]);

    // Update the value
    $model->flexy->field1 = 'updated value';
    $model->save();

    // Check that value was updated
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'field1',
        'value_string' => 'updated value',
        'schema_code' => 'test',
    ]);
});

it('handles field deletion correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set values
    $model->flexy->field1 = 'value1';
    $model->flexy->field2 = 42;
    $model->save();

    // Verify values exist
    expect(DB::table('ff_field_values')
        ->where('model_type', ExampleFlexyModel::class)
        ->where('schema_code', 'test')
        ->count())->toBe(2);

    // Remove a field
    ExampleFlexyModel::removeFieldFromSchema('test', 'field1');

    // Create another model and assign to schema
    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field2 = 100;
    $model2->save();

    // Check that field1 is not accessible but field2 is
    $model2->refresh();
    expect(fn () => $model2->flexy->field1)->toThrow(\Exception::class);
    expect($model2->flexy->field2)->toBe(100);
});
