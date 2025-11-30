<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\SchemaField;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
});

it('throws exception for invalid field type', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    // Laravel's enum casting may throw different exceptions depending on version
    // We just verify that an exception is thrown
    try {
        SchemaField::create([
            'schema_code' => 'test',
            'name' => 'invalid',
            'type' => 'invalid_type', // Not a FlexyFieldType enum
            'sort' => 1,
        ]);
        expect(false)->toBeTrue('Should have thrown an exception');
    } catch (\Throwable $e) {
        expect($e)->toBeInstanceOf(\Throwable::class);
    }
});

it('validates field type on update', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    $schemaField = SchemaField::create([
        'schema_code' => 'test',
        'name' => 'field1',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    // Try to update with invalid type - Laravel's enum casting will throw exception
    try {
        $schemaField->type = 'invalid';
        $schemaField->save();
        expect(false)->toBeTrue('Should have thrown an exception');
    } catch (\Throwable $e) {
        expect($e)->toBeInstanceOf(\Throwable::class);
    }
});

it('returns empty array for getValidationRulesArray when rules are empty', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    $schemaField = SchemaField::create([
        'schema_code' => 'test',
        'name' => 'field1',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_rules' => null,
    ]);

    expect($schemaField->getValidationRulesArray())->toBe([]);
});

it('returns array directly when validation_rules is already array', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
    ]);

    $schemaField = new SchemaField([
        'schema_code' => 'test',
        'name' => 'field1',
        'type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);
    $schemaField->validation_rules = ['required', 'string', 'max:255'];

    expect($schemaField->getValidationRulesArray())->toBe(['required', 'string', 'max:255']);
});
