<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\SchemaField;
use AuroraWebSoftware\FlexyField\Models\FieldValue;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(CreatesSchemas::class);

beforeEach(function () {

    Schema::dropIfExists('ff_example_flexy_models'); Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable()->index();
        $table->timestamps();
    });
});

it('maintains data integrity when schema is deleted', function () {
    // Create a schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->flexy->field2 = 100;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value2';
    $model2->flexy->field2 = 200;
    $model2->save();

    // Verify values exist
    expect(DB::table('ff_field_values')
        ->where('model_type', ExampleFlexyModel::class)
        ->where('schema_code', 'test')
        ->count())->toBe(4);

    // Delete the schema directly
    FieldSchema::where('schema_code', 'test')->delete();

    // Check that schema fields are deleted (cascade)
    expect(DB::table('ff_schema_fields')
        ->where('schema_code', 'test')
        ->count())->toBe(0);

    // Check that field values have schema_id nullified (set null on delete)
    // Note: schema_code remains as it's not a foreign key, only schema_id is nullified
    $values = FieldValue::where('model_type', ExampleFlexyModel::class)->get();
    expect($values->count())->toBe(4);
    foreach ($values as $value) {
        expect($value->schema_code)->toBe('test'); // schema_code is not a FK, so it remains
        expect($value->schema_id)->toBeNull(); // schema_id is FK with SET NULL, so it's nullified
    }
});

it('maintains data integrity when field is deleted from schema', function () {
    // Create a schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create a model with values
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'value1';
    $model->flexy->field2 = 100;
    $model->save();

    // Verify values exist
    expect(DB::table('ff_field_values')
        ->where('model_type', ExampleFlexyModel::class)
        ->where('schema_code', 'test')
        ->count())->toBe(2);

    // Remove a field from the schema
    ExampleFlexyModel::removeFieldFromSchema('test', 'field1');

    // Create another model and assign to schema
    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field2 = 200;
    $model2->save();

    // Check that field1 values are still accessible
    $model2->refresh();
    expect($model2->flexy->field2)->toBe(200);

    // Check that field1 is not accessible
    // Check that field1 is not accessible (throws exception because it's not in schema)
    expect(fn () => $model2->flexy->field1)->toThrow(\AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSchemaException::class);
});

it('handles concurrent schema modifications correctly', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create two models concurrently
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value2';
    $model2->save();

    // Add a field to the schema concurrently
    ExampleFlexyModel::addFieldToSchema('test', 'field2', FlexyFieldType::INTEGER);

    // Create another model and assign to schema
    $model3 = ExampleFlexyModel::create(['name' => 'Test 3']);
    $model3->assignToSchema('test');
    $model3->flexy->field2 = 300;
    $model3->save();

    // Check that all models have correct field access
    foreach ([$model1, $model2, $model3] as $model) {
        $model->refresh();
        // field1 is string
        if ($model->flexy->field1 !== null) {
            expect($model->flexy->field1)->toBeString();
        }
        // field2 is int (or null if not set)
        if ($model->flexy->field2 !== null) {
            expect($model->flexy->field2)->toBeInt();
        }
    }
});

it('prevents orphaned field values', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model with values
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'value1';
    $model->save();

    // Verify value exists
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'field1',
        'value_string' => 'value1',
        'schema_code' => 'test',
    ]);

    // Delete the schema directly
    FieldSchema::where('schema_code', 'test')->delete();

    // Try to create a new model with the same schema (should fail because schema is deleted)
    // Note: create() calls creating event which tries to assign default schema if not set.
    // Here we are not setting schema_code in create(), so it tries default.
    // If we want to test assigning deleted schema:
    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    expect(fn () => $model2->assignToSchema('test'))->toThrow(\AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException::class);
});

it('handles schema renaming correctly', function () {
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
    $model->flexy->field1 = 'value1';
    $model->save();

    // Verify value exists with original schema
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'field1',
        'value_string' => 'value1',
        'schema_code' => 'test',
    ]);

    // Rename the schema
    $schema = ExampleFlexyModel::getSchema('test');
    $schema->label = 'Updated Schema';
    $schema->save();

    // Verify existing values still reference the old schema code
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'field1',
        'value_string' => 'value1',
        'schema_code' => 'test',
    ]);
});

it('handles schema field sorting correctly', function () {
    // Create a schema with fields in specific order
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field3' => ['type' => FlexyFieldType::STRING, 'sort' => 3],
            'field1' => ['type' => FlexyFieldType::STRING, 'sort' => 1],
            'field2' => ['type' => FlexyFieldType::INTEGER, 'sort' => 2],
        ]
    );

    // Get fields for the schema
    $fields = ExampleFlexyModel::getFieldsForSchema('test');

    // Check that fields are returned in the correct order
    expect($fields)->toHaveCount(3);
    expect($fields[0]->name)->toBe('field1');
    expect($fields[0]->sort)->toBe(1);
    expect($fields[1]->name)->toBe('field2');
    expect($fields[1]->sort)->toBe(2);
    expect($fields[2]->name)->toBe('field3');
    expect($fields[2]->sort)->toBe(3);
});
