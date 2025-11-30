<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSchema;
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

it('stores group in metadata', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'voltage',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'Power Specs']
    );

    expect($field->metadata)->toBeArray()
        ->and($field->metadata['group'])->toBe('Power Specs');
});

it('returns group name via getGroup()', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'voltage',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'Technical Specs']
    );

    expect($field->getGroup())->toBe('Technical Specs');
});

it('returns null for ungrouped fields', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'name',
        fieldType: FlexyFieldType::STRING
    );

    expect($field->getGroup())->toBeNull()
        ->and($field->hasGroup())->toBeFalse();
});

it('treats empty string as ungrouped', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'name',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => '']
    );

    expect($field->getGroup())->toBeNull()
        ->and($field->hasGroup())->toBeFalse();
});

it('hasGroup returns true for grouped fields', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'voltage',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'Power']
    );

    expect($field->hasGroup())->toBeTrue();
});

it('organizes fields by group via getFieldsGrouped()', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    // Add fields to different groups
    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'voltage',
        fieldType: FlexyFieldType::STRING,
        sort: 1,
        fieldMetadata: ['group' => 'Power Specs']
    );

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'weight',
        fieldType: FlexyFieldType::DECIMAL,
        sort: 2,
        fieldMetadata: ['group' => 'Physical']
    );

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'name',
        fieldType: FlexyFieldType::STRING,
        sort: 3
        // No group
    );

    $schema = FieldSchema::where('schema_code', 'test')->first();
    $grouped = $schema->getFieldsGrouped();

    expect($grouped)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($grouped->keys()->toArray())->toContain('Power Specs')
        ->and($grouped->keys()->toArray())->toContain('Physical')
        ->and($grouped->keys()->toArray())->toContain('Ungrouped')
        ->and($grouped['Power Specs'])->toHaveCount(1)
        ->and($grouped['Physical'])->toHaveCount(1)
        ->and($grouped['Ungrouped'])->toHaveCount(1);
});

it('sorts groups alphabetically with Ungrouped last', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field1',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'Zebra Group']
    );

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field2',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'Alpha Group']
    );

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field3',
        fieldType: FlexyFieldType::STRING
        // Ungrouped
    );

    $schema = FieldSchema::where('schema_code', 'test')->first();
    $grouped = $schema->getFieldsGrouped();
    $keys = $grouped->keys()->toArray();

    expect($keys[0])->toBe('Alpha Group')
        ->and($keys[1])->toBe('Zebra Group')
        ->and($keys[2])->toBe('Ungrouped');
});

it('sorts fields within groups by sort column', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field_c',
        fieldType: FlexyFieldType::STRING,
        sort: 30,
        fieldMetadata: ['group' => 'Group A']
    );

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field_a',
        fieldType: FlexyFieldType::STRING,
        sort: 10,
        fieldMetadata: ['group' => 'Group A']
    );

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field_b',
        fieldType: FlexyFieldType::STRING,
        sort: 20,
        fieldMetadata: ['group' => 'Group A']
    );

    $schema = FieldSchema::where('schema_code', 'test')->first();
    $grouped = $schema->getFieldsGrouped();
    $fields = $grouped['Group A'];

    expect($fields[0]->name)->toBe('field_a')
        ->and($fields[1]->name)->toBe('field_b')
        ->and($fields[2]->name)->toBe('field_c');
});

it('handles special characters in group names', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    $field = ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field1',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'Güç Özellikleri 🔋']
    );

    expect($field->getGroup())->toBe('Güç Özellikleri 🔋');
});

it('handles case sensitivity in group names', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field1',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'Power Specs']
    );

    ExampleFlexyModel::addFieldToSchema(
        schemaCode: 'test',
        fieldName: 'field2',
        fieldType: FlexyFieldType::STRING,
        fieldMetadata: ['group' => 'power specs']
    );

    $schema = FieldSchema::where('schema_code', 'test')->first();
    $grouped = $schema->getFieldsGrouped();

    // Both groups should exist separately (case preserved)
    expect($grouped->keys()->toArray())->toContain('Power Specs')
        ->and($grouped->keys()->toArray())->toContain('power specs');
});
