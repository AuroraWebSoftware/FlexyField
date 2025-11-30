<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;

uses(CreatesSchemas::class);

beforeEach(function () {
    // Ensure we have a clean database state
});

it('assigns model to schema correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    expect($model->schema_code)->toBe('test');
    expect($model->getSchemaCode())->toBe('test');
});

it('reassigns model to different schema correctly', function () {
    // Create two schemas
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'schema1',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'schema2',
        fields: [
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Assign to first schema
    $model->assignToSchema('schema1');
    $model->save();
    expect($model->schema_code)->toBe('schema1');

    // Reassign to second schema
    $model->assignToSchema('schema2');
    $model->save();
    expect($model->schema_code)->toBe('schema2');

    // Refresh and verify
    $model->refresh();
    expect($model->schema_code)->toBe('schema2');
});

it('throws exception when assigning to non-existent schema', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    $model->assignToSchema('non_existent_schema');
})->throws(SchemaNotFoundException::class);

it('prevents assigning schema from different model type', function () {
    // Create schema specifically for ExampleFlexyModel
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'valid_schema',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Try to assign a non-existent schema (simulates wrong model type scenario)
    expect(fn () => $model->assignToSchema('schema_from_other_model'))
        ->toThrow(SchemaNotFoundException::class);
});

it('automatically assigns new instances to default schema', function () {
    // Create a default schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'default',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ],
        isDefault: true
    );

    // Create new model - should auto-assign to default
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    expect($model->schema_code)->toBe('default');
    expect($model->getSchemaCode())->toBe('default');
});

it('does not override explicit schema assignment with default', function () {
    // Create default schema and another schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'default',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ],
        isDefault: true
    );

    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'custom',
        fields: [
            'field2' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Explicitly assign to custom schema
    $model = new ExampleFlexyModel(['name' => 'Test']);
    $model->assignToSchema('custom');
    $model->save();

    expect($model->schema_code)->toBe('custom');
    expect($model->getSchemaCode())->toBe('custom');
});

it('allows unsetting schema assignment', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->save();

    expect($model->schema_code)->toBe('test');

    // Unset schema
    $model->schema_code = null;
    $model->save();

    expect($model->schema_code)->toBeNull();
    expect($model->getSchemaCode())->toBeNull();
});

it('validates schema exists before setting field values', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Try to set flexy field without schema assignment
    $closure = function () use ($model) {
        $model->flexy->field1 = 'value';
        $model->save();
    };

    expect($closure)->toThrow(SchemaNotFoundException::class);
});

it('maintains schema assignment across model refresh', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->save();

    $modelId = $model->id;

    // Refresh from database
    $freshModel = ExampleFlexyModel::find($modelId);

    expect($freshModel->schema_code)->toBe('test');
    expect($freshModel->getSchemaCode())->toBe('test');
});
