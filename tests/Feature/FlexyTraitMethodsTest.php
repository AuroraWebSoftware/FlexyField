<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\SchemaField;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
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

it('provides access to schema management methods', function () {
    // Check that all schema management methods are available
    expect(method_exists(ExampleFlexyModel::class, 'createSchema'))->toBeTrue();
    expect(method_exists(ExampleFlexyModel::class, 'getSchema'))->toBeTrue();
    expect(method_exists(ExampleFlexyModel::class, 'getAllSchemas'))->toBeTrue();
    expect(method_exists(ExampleFlexyModel::class, 'deleteSchema'))->toBeTrue();
});

it('provides access to field management methods', function () {
    // Check that all field management methods are available
    expect(method_exists(ExampleFlexyModel::class, 'addFieldToSchema'))->toBeTrue();
    expect(method_exists(ExampleFlexyModel::class, 'removeFieldFromSchema'))->toBeTrue();
    expect(method_exists(ExampleFlexyModel::class, 'getFieldsForSchema'))->toBeTrue();
});

it('provides access to instance methods', function () {
    // Check that all instance methods are available
    expect(method_exists(ExampleFlexyModel::class, 'assignToSchema'))->toBeTrue();
    expect(method_exists(ExampleFlexyModel::class, 'getSchemaCode'))->toBeTrue();
    expect(method_exists(ExampleFlexyModel::class, 'getAvailableFields'))->toBeTrue();
});

it('provides access to relationship methods', function () {
    // Check that relationship methods are available
    expect(method_exists(ExampleFlexyModel::class, 'schema'))->toBeTrue();
});

it('creates schema correctly', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Check that schema was created
    expect($schema)->toBeInstanceOf(FieldSchema::class);
    expect($schema->schema_code)->toBe('test');
    expect($schema->label)->toBe('Test Schema');
    expect($schema->description)->toBe('Test schema description');
    expect($schema->is_default)->toBeFalse();
});

it('gets schema correctly', function () {
    // Create a schema
    ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Get the schema
    $schema = ExampleFlexyModel::getSchema('test');

    // Check that schema was retrieved
    expect($schema)->toBeInstanceOf(FieldSchema::class);
    expect($schema->schema_code)->toBe('test');
    expect($schema->label)->toBe('Test Schema');
    expect($schema->description)->toBe('Test schema description');
    expect($schema->is_default)->toBeFalse();
});

it('gets all schemas correctly', function () {
    // Create multiple schemas
    ExampleFlexyModel::createSchema('schema1', 'Schema 1', 'Description 1', null, false);
    ExampleFlexyModel::createSchema('schema2', 'Schema 2', 'Description 2', null, false);
    ExampleFlexyModel::createSchema('default', 'Default Schema', 'Default Description', null, true);

    // Get all schemas
    $schemas = ExampleFlexyModel::getAllSchemas();

    // Check that all schemas were retrieved
    expect($schemas)->toHaveCount(3);
    expect($schemas->pluck('schema_code')->toArray())->toContain('schema1', 'schema2', 'default');
    expect($schemas->firstWhere('is_default', true)->schema_code)->toBe('default');
});

it('deletes schema correctly', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Check that schema exists
    expect(ExampleFlexyModel::getSchema('test'))->not->toBeNull();

    // Delete the schema
    $result = ExampleFlexyModel::deleteSchema('test');

    // Check that schema was deleted
    expect($result)->toBeTrue();
    expect(ExampleFlexyModel::getSchema('test'))->toBeNull();
});

it('throws exception when deleting non-existent schema', function () {
    // Try to delete non-existent schema
    $result = ExampleFlexyModel::deleteSchema('non_existent');

    // Check that false is returned
    expect($result)->toBeFalse();
});

it('throws exception when deleting schema in use', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );
    
    // Add field to schema
    ExampleFlexyModel::addFieldToSchema('test', 'field1', FlexyFieldType::STRING);

    // Create a model using the schema
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'test value';
    $model->save();

    // Try to delete the schema (should fail)
    expect(fn () => ExampleFlexyModel::deleteSchema('test'))->toThrow(\Exception::class);
});

it('adds field to schema correctly', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Add a field to the schema
    $field = ExampleFlexyModel::addFieldToSchema(
        'test',
        'field1',
        FlexyFieldType::STRING,
        1,
        'required|string',
        null,
        null
    );

    // Check that field was created
    expect($field)->toBeInstanceOf(SchemaField::class);
    expect($field->schema_code)->toBe('test');
    expect($field->name)->toBe('field1');
    expect($field->type)->toBe(FlexyFieldType::STRING);
    expect($field->sort)->toBe(1);
    expect($field->validation_rules)->toBe('required|string');
});

it('removes field from schema correctly', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Add a field to the schema
    $field = ExampleFlexyModel::addFieldToSchema(
        'test',
        'field1',
        FlexyFieldType::STRING,
        1,
        'required|string',
        null,
        null
    );

    // Check that field exists
    expect(ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'field1'))->not->toBeNull();

    // Remove the field
    $result = ExampleFlexyModel::removeFieldFromSchema('test', 'field1');

    // Check that field was removed
    expect($result)->toBeTrue();
    expect(ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'field1'))->toBeNull();
});

it('gets fields for schema correctly', function () {
    // Create a schema with multiple fields
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Add multiple fields with different sort orders
    ExampleFlexyModel::addFieldToSchema('test', 'field3', FlexyFieldType::STRING, 3);
    ExampleFlexyModel::addFieldToSchema('test', 'field1', FlexyFieldType::STRING, 1);
    ExampleFlexyModel::addFieldToSchema('test', 'field2', FlexyFieldType::INTEGER, 2);

    // Get fields for the schema
    $fields = ExampleFlexyModel::getFieldsForSchema('test');

    // Check that fields were retrieved in correct order
    expect($fields)->toHaveCount(3);
    expect($fields[0]->name)->toBe('field1');
    expect($fields[0]->sort)->toBe(1);
    expect($fields[1]->name)->toBe('field2');
    expect($fields[1]->sort)->toBe(2);
    expect($fields[2]->name)->toBe('field3');
    expect($fields[2]->sort)->toBe(3);
});

it('assigns model to schema correctly', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Assign the model to the schema
    $model->assignToSchema('test');

    // Check that model was assigned
    expect($model->schema_code)->toBe('test');
    expect($model->getSchemaCode())->toBe('test');
});

it('gets schema code correctly', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Assign the model to the schema
    $model->assignToSchema('test');

    // Get the schema code
    $schemaCode = $model->getSchemaCode();

    // Check that schema code was retrieved
    expect($schemaCode)->toBe('test');
});

it('gets available fields correctly', function () {
    // Create a schema with fields
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Add fields to the schema
    ExampleFlexyModel::addFieldToSchema('test', 'field1', FlexyFieldType::STRING, 1);
    ExampleFlexyModel::addFieldToSchema('test', 'field2', FlexyFieldType::INTEGER, 2);

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Assign the model to the schema
    $model->assignToSchema('test');

    // Get available fields
    $fields = $model->getAvailableFields();

    // Check that fields were retrieved
    expect($fields)->toHaveCount(2);
    expect($fields->pluck('name')->toArray())->toContain('field1', 'field2');
});

it('provides schema relationship correctly', function () {
    // Create a schema
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        isDefault: false
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Assign the model to the schema
    $model->assignToSchema('test');

    // Get the schema relationship
    $schema = $model->schema;

    // Check that relationship works
    expect($schema)->toBeInstanceOf(FieldSchema::class);
    expect($schema->schema_code)->toBe('test');
});
