<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
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

    $this->cleanupTestData();
});

afterEach(function () {
    Schema::dropIfExists('ff_example_flexy_models');
    $this->cleanupTestData();
});

it('stores label in database', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'battery_capacity_mah',
        fieldType: FlexyFieldType::INTEGER,
        label: 'Battery Capacity'
    );

    expect($field->label)->toBe('Battery Capacity')
        ->and($field->name)->toBe('battery_capacity_mah');
});

it('getLabel returns label when present', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'voltage',
        fieldType: FlexyFieldType::STRING,
        label: 'Voltage Rating'
    );

    expect($field->getLabel())->toBe('Voltage Rating');
});

it('getLabel falls back to name when label is null', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'battery_capacity_mah',
        fieldType: FlexyFieldType::INTEGER
    );

    expect($field->label)->toBeNull()
        ->and($field->getLabel())->toBe('battery_capacity_mah');
});

it('stores and retrieves placeholder from metadata', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'voltage',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['placeholder' => 'Enter voltage in V']
    );

    expect($field->getPlaceholder())->toBe('Enter voltage in V');
});

it('stores and retrieves hint from metadata', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'capacity',
        fieldType: FlexyFieldType::INTEGER,
        fieldMetadata: ['hint' => 'Max 5000mAh']
    );

    expect($field->getHint())->toBe('Max 5000mAh');
});

it('returns null for missing placeholder', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'name',
        fieldType: FlexyFieldType::STRING
    );

    expect($field->getPlaceholder())->toBeNull();
});

it('returns null for missing hint', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'name',
        fieldType: FlexyFieldType::STRING
    );

    expect($field->getHint())->toBeNull();
});

it('handles all UI hints together', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'battery_capacity_mah',
        fieldType: FlexyFieldType::INTEGER,
        label: 'Battery Capacity',
        fieldMetadata: [
            'placeholder' => 'Enter mAh',
            'hint' => 'Between 1000-5000mAh',
        ]
    );

    expect($field->getLabel())->toBe('Battery Capacity')
        ->and($field->getPlaceholder())->toBe('Enter mAh')
        ->and($field->getHint())->toBe('Between 1000-5000mAh');
});

it('handles empty string label as null', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field_name',
        fieldType: FlexyFieldType::STRING,
        label: ''
    );

    // Empty string is stored as-is but getLabel() falls back to name
    expect($field->getLabel())->toBe('field_name');
});

it('preserves special characters in UI hints', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'price',
        fieldType: FlexyFieldType::DECIMAL,
        label: 'Fiyat 💰',
        fieldMetadata: [
            'placeholder' => 'Örnek: 99.99 ₺',
            'hint' => 'Maksimum 10.000 ₺',
        ]
    );

    expect($field->getLabel())->toBe('Fiyat 💰')
        ->and($field->getPlaceholder())->toBe('Örnek: 99.99 ₺')
        ->and($field->getHint())->toBe('Maksimum 10.000 ₺');
});
