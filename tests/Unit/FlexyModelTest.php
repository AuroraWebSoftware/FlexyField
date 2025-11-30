<?php

use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;

uses(CreatesSchemas::class);

beforeEach(function () {
});

it('can instantiate Flexy model', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: []
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    expect($flexy)->toBeInstanceOf(\AuroraWebSoftware\FlexyField\Models\Flexy::class);
    expect($flexy->_model_type)->toBe(ExampleFlexyModel::class);
    expect($flexy->_model_id)->toBe(1);
});

it('can set and get attributes', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
            'int_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::INTEGER],
            'bool_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::BOOLEAN],
            'json_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::JSON],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set attributes
    $flexy->test_field = 'test value';
    $flexy->int_field = 42;
    $flexy->bool_field = true;
    $flexy->json_field = ['key' => 'value'];

    // Get attributes
    expect($flexy->test_field)->toBe('test value');
    expect($flexy->int_field)->toBe(42);
    expect($flexy->bool_field)->toBeTrue();
    expect($flexy->json_field)->toEqual(['key' => 'value']);
});

it('can set and get attributes via array access', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
            'int_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::INTEGER],
            'bool_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::BOOLEAN],
            'json_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::JSON],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set attributes via array access
    $flexy['test_field'] = 'test value';
    $flexy['int_field'] = 42;
    $flexy['bool_field'] = true;
    $flexy['json_field'] = ['key' => 'value'];

    // Get attributes
    expect($flexy->test_field)->toBe('test value');
    expect($flexy->int_field)->toBe(42);
    expect($flexy->bool_field)->toBeTrue();
    expect($flexy->json_field)->toEqual(['key' => 'value']);
});

it('can check if field is dirty', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set a field
    $flexy->test_field = 'test value';
    // Note: In recent Laravel/Eloquent, setting attribute on new model might not be considered dirty if it wasn't hydrated?
    // Actually, setting attribute on new model makes it dirty (in attributes array).
    // But syncOriginal() is not called.
    // Let's check isDirty().
    expect($flexy->isDirty('test_field'))->toBeTrue();

    // Sync original to simulate saved state
    $flexy->syncOriginal();
    expect($flexy->isDirty('test_field'))->toBeFalse();

    // Change value
    $flexy->test_field = 'different value';
    expect($flexy->isDirty('test_field'))->toBeTrue();
});

it('can get dirty fields', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
            'int_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::INTEGER],
            'bool_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::BOOLEAN],
            'json_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::JSON],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set multiple fields
    $flexy->test_field = 'test value';
    $flexy->int_field = 42;
    $flexy->bool_field = true;
    $flexy->json_field = ['key' => 'value'];

    // Get dirty fields
    // Get dirty fields
    $dirtyFields = $flexy->getDirty();

    // Check that our set fields are dirty
    expect(array_key_exists('test_field', $dirtyFields))->toBeTrue();
    expect(array_key_exists('int_field', $dirtyFields))->toBeTrue();
    expect(array_key_exists('bool_field', $dirtyFields))->toBeTrue();
    expect(array_key_exists('json_field', $dirtyFields))->toBeTrue();
});

it('can get original values', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
            'int_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::INTEGER],
            'bool_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::BOOLEAN],
            'json_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::JSON],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set values
    $flexy->test_field = 'test value';
    $flexy->int_field = 42;
    $flexy->bool_field = true;
    $flexy->json_field = ['key' => 'value'];

    // Sync original
    $flexy->syncOriginal();

    // Get original values
    $originalTestField = $flexy->getOriginal('test_field');
    $originalIntField = $flexy->getOriginal('int_field');
    $originalBoolField = $flexy->getOriginal('bool_field');
    $originalJsonField = $flexy->getOriginal('json_field');

    // Check original values
    expect($originalTestField)->toBe('test value');
    expect($originalIntField)->toBe(42);
    expect($originalBoolField)->toBeTrue();
    expect($originalJsonField)->toEqual(['key' => 'value']);

    // Change values
    $flexy->test_field = 'different value';
    $flexy->int_field = 100;
    $flexy->bool_field = false;

    // Check that original values are unchanged
    expect($flexy->getOriginal('test_field'))->toBe('test value');
    expect($flexy->getOriginal('int_field'))->toBe(42);
    expect($flexy->getOriginal('bool_field'))->toBeTrue();
    expect($flexy->getOriginal('json_field'))->toEqual(['key' => 'value']);
});

it('can get attributes', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
            'int_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::INTEGER],
            'bool_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::BOOLEAN],
            'json_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::JSON],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set attributes
    $flexy->test_field = 'test value';
    $flexy->int_field = 42;
    $flexy->bool_field = true;
    $flexy->json_field = ['key' => 'value'];

    // Get attributes
    $attributes = $flexy->getAttributes();

    // Check that attributes are correct
    expect($attributes['test_field'])->toBe('test value');
    expect($attributes['int_field'])->toBe(42);
    expect($attributes['bool_field'])->toBeTrue();
    expect($attributes['json_field'])->toEqual(['key' => 'value']);
});

it('can check if attribute exists', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set attributes
    $flexy->test_field = 'test value';

    // Check that attribute exists (using isset or array access)
    expect(isset($flexy->test_field))->toBeTrue();
    expect($flexy['test_field'])->toBe('test value');

    // Check that attribute does not exist
    expect(isset($flexy->non_existent_field))->toBeFalse();
});

it('can get attribute value with default', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'test_field' => ['type' => \AuroraWebSoftware\FlexyField\Enums\FlexyFieldType::STRING],
        ]
    );

    $flexy = new \AuroraWebSoftware\FlexyField\Models\Flexy;
    $flexy->_model_type = ExampleFlexyModel::class;
    $flexy->_model_id = 1;
    $flexy->_schema_code = 'test';

    // Set attributes
    $flexy->test_field = 'test value';

    // Get attribute value
    expect($flexy->getAttribute('test_field'))->toBe('test value');

    // Get attribute value with default (Eloquent doesn't support default in getAttribute, returns null)
    // But we can use data_get or similar helper if needed, or just check null.
    // Standard Eloquent getAttribute returns null if not found.
    expect($flexy->getAttribute('non_existent_field'))->toBeNull();
});
