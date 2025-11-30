<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\FieldValue;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(CreatesSchemas::class);

beforeEach(function () {
    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable();
        $table->timestamps();
    });
});

it('creates pivot view with all fields', function () {
    // Create a schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
            'field3' => ['type' => FlexyFieldType::DECIMAL],
        ]
    );

    // Create a model with values
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'value1';
    $model->flexy->field2 = 123;
    $model->flexy->field3 = 45.67;
    $model->save();

    // Check that view was created with all fields
    $viewColumns = $this->getViewColumns('ff_values_pivot_view');
    $columnNames = array_map(fn($col) => $col->Field, $viewColumns);

    expect($columnNames)->toContain('flexy_field1')
        ->and($columnNames)->toContain('flexy_field2')
        ->and($columnNames)->toContain('flexy_field3');
});

it('recreates view when new field is added', function () {
    // Create initial schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create model and save initial value
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'value1';
    $model->save();

    // Get initial view columns
    $initialColumns = $this->getViewColumns('ff_values_pivot_view');
    $initialColumnNames = array_map(fn($col) => $col->Field, $initialColumns);

    expect($initialColumnNames)->toContain('flexy_field1')
        ->and($initialColumnNames)->not->toContain('flexy_field2');

    // Add new field to schema
    ExampleFlexyModel::addFieldToSchema('test', 'field2', FlexyFieldType::INTEGER);

    // Create another model with the new field
    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field2 = 456;
    $model2->save();

    // Check that view was recreated with new field
    $updatedColumns = $this->getViewColumns('ff_values_pivot_view');
    $updatedColumnNames = array_map(fn($col) => $col->Field, $updatedColumns);

    expect($updatedColumnNames)->toContain('flexy_field1')
        ->and($updatedColumnNames)->toContain('flexy_field2');
});

it('does not recreate view when existing field is updated', function () {
    // Create schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create model and save initial values
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'value1';
    $model->flexy->field2 = 123;
    $model->save();

    // Get initial view columns
    $initialColumns = $this->getViewColumns('ff_values_pivot_view');
    $initialColumnNames = array_map(fn($col) => $col->Field, $initialColumns);

    // Update existing field value (should not trigger view recreation)
    $model->flexy->field1 = 'updated value';
    $model->save();

    // Check that view columns are unchanged
    $updatedColumns = $this->getViewColumns('ff_values_pivot_view');
    $updatedColumnNames = array_map(fn($col) => $col->Field, $updatedColumns);

    expect($updatedColumnNames)->toEqual($initialColumnNames);
});

it('handles empty view creation', function () {
    // Create schema without fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'empty',
        fields: []
    );

    // Create model without any flexy fields
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('empty');
    $model->save();

    // Check that view was created even with no fields
    $this->assertTrue(Schema::hasView('ff_values_pivot_view'));
});

it('tracks field names in schema tracking table', function () {
    // Create schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Check that fields are tracked
    $trackedFields = DB::table('ff_view_schema')->pluck('name')->toArray();

    expect($trackedFields)->toContain('field1')
        ->and($trackedFields)->toContain('field2');
});

it('force recreates view and rebuilds tracking', function () {
    // Create schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create model with value
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'value1';
    $model->save();

    // Verify initial state
    $this->assertDatabaseHas('ff_view_schema', ['name' => 'field1']);

    // Force recreate view
    FlexyField::forceRecreateView();

    // Check that tracking table is rebuilt with existing fields
    $trackedFields = DB::table('ff_view_schema')->pluck('name')->toArray();
    expect($trackedFields)->toContain('field1');
});
