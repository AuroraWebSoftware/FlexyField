<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\SchemaField;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
});

it('can create a schema field', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    $schemaField = SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'size',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_rules' => 'required|string',
    ]);

    expect($schemaField)->toBeInstanceOf(SchemaField::class)
        ->and($schemaField->schema_code)->toBe('shoes')
        ->and($schemaField->name)->toBe('size')
        ->and($schemaField->type)->toBe(FlexyFieldType::STRING)
        ->and($schemaField->validation_rules)->toBe('required|string');
});

it('casts type to enum', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    $schemaField = SchemaField::create([
        'schema_code' => 'test',
        'name' => 'count',
        'type' => FlexyFieldType::INTEGER,
        'sort' => 1,
    ]);

    expect($schemaField->type)->toBeInstanceOf(FlexyFieldType::class)
        ->and($schemaField->type)->toBe(FlexyFieldType::INTEGER);
});

it('stores validation messages as JSON', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    $messages = ['required' => 'This field is required'];

    $schemaField = SchemaField::create([
        'schema_code' => 'test',
        'name' => 'email',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_messages' => $messages,
    ]);

    expect($schemaField->fresh()->validation_messages)->toBe($messages);
});

it('stores field metadata as JSON', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    $metadata = ['placeholder' => 'Enter size', 'help_text' => 'US sizing'];

    $schemaField = SchemaField::create([
        'schema_code' => 'test',
        'name' => 'size',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
        'metadata' => $metadata,
    ]);

    expect($schemaField->fresh()->metadata)->toEqual($metadata);
});

it('has schema relationship', function () {
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    $schemaField = SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'size',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    expect($schemaField->schema)->toBeInstanceOf(FieldSchema::class)
        ->and($schemaField->schema->schema_code)->toBe('shoes');
});

it('gets validation rules as array', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    $schemaField = SchemaField::create([
        'schema_code' => 'test',
        'name' => 'email',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_rules' => 'required|email|max:255',
    ]);

    $rulesArray = $schemaField->getValidationRulesArray();

    expect($rulesArray)->toBeArray()
        ->and($rulesArray)->toBe(['required', 'email', 'max:255']);
});

it('enforces unique constraint on schema_code and name', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'size',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    expect(fn () => SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'size',
        'type' => FlexyFieldType::INTEGER,
        'sort' => 2,
    ]))->toThrow(\Exception::class);
});

it('cascades delete when schema is deleted', function () {
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'size',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'color',
        'type' => FlexyFieldType::STRING,
        'sort' => 2,
    ]);

    expect(SchemaField::where('schema_code', 'shoes')->count())->toBe(2);

    $schema->delete();

    expect(SchemaField::where('schema_code', 'shoes')->count())->toBe(0);
});
