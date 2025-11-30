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
});

it('stores schema metadata correctly', function () {
    $metadata = [
        'category' => 'product',
        'priority' => 1,
        'custom_properties' => [
            'display_in_catalog' => true,
            'searchable' => true,
        ],
    ];

    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        metadata: $metadata,
        isDefault: false
    );

    expect($schema->metadata)->toEqual($metadata);
});

it('retrieves schema metadata correctly', function () {
    $metadata = [
        'category' => 'product',
        'priority' => 1,
    ];

    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        description: 'Test schema description',
        metadata: $metadata,
        isDefault: false
    );

    $retrievedSchema = ExampleFlexyModel::getSchema('test');
    expect($retrievedSchema->metadata)->toEqual($metadata);
});

it('updates schema metadata correctly', function () {
    $initialMetadata = ['category' => 'product'];
    $schema = ExampleFlexyModel::createSchema(
        schemaCode: 'test',
        label: 'Test Schema',
        metadata: $initialMetadata,
        isDefault: false
    );

    $updatedMetadata = [
        'category' => 'service',
        'priority' => 2,
    ];

    $schema->metadata = $updatedMetadata;
    $schema->save();

    $retrievedSchema = ExampleFlexyModel::getSchema('test');
    expect($retrievedSchema->metadata)->toEqual($updatedMetadata);
});

it('stores field metadata correctly', function () {
    $metadata = [
        'placeholder' => 'Enter product name',
        'help_text' => 'The name of the product',
        'validation_options' => [
            'min_length' => 3,
            'max_length' => 100,
        ],
    ];

    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        'test',
        'product_name',
        FlexyFieldType::STRING,
        sort: 1,
        fieldMetadata: $metadata
    );

    $field = ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'product_name');
    expect($field->metadata)->toEqual($metadata);
});

it('retrieves field metadata correctly', function () {
    $metadata = [
        'placeholder' => 'Enter product name',
        'help_text' => 'The name of the product',
    ];

    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        'test',
        'product_name',
        FlexyFieldType::STRING,
        sort: 1,
        fieldMetadata: $metadata
    );

    $field = ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'product_name');
    expect($field->metadata)->toEqual($metadata);
});

it('updates field metadata correctly', function () {
    $initialMetadata = ['placeholder' => 'Enter name'];
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'name',
        FlexyFieldType::STRING,
        sort: 1,
        fieldMetadata: $initialMetadata
    );

    $updatedMetadata = ['placeholder' => 'Enter full name'];
    $field = ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'name');
    $field->metadata = $updatedMetadata;
    $field->save();

    $retrievedField = ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'name');
    expect($retrievedField->metadata)->toEqual($updatedMetadata);
});

it('handles complex nested metadata', function () {
    $complexMetadata = [
        'ui_config' => [
            'component' => 'text-input',
            'props' => [
                'maxlength' => 100,
                'required' => true,
            ],
        ],
        'validation_rules' => [
            'client_side' => [
                'pattern' => '^[a-zA-Z0-9]+$',
            ],
            'server_side' => [
                'required',
                'max:100',
            ],
        ],
        'business_logic' => [
            'auto_generate' => false,
            'sync_with_external' => true,
        ],
    ];

    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        'test',
        'complex_field',
        FlexyFieldType::STRING,
        sort: 1,
        fieldMetadata: $complexMetadata
    );

    $field = ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'complex_field');
    expect($field->metadata)->toEqual($complexMetadata);
});

it('handles null metadata correctly', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        'test',
        'field_with_null_metadata',
        FlexyFieldType::STRING,
        sort: 1,
        fieldMetadata: null
    );

    $field = ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'field_with_null_metadata');
    expect($field->metadata)->toBeNull();
});

it('handles empty metadata correctly', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');

    ExampleFlexyModel::addFieldToSchema(
        'test',
        'field_with_empty_metadata',
        FlexyFieldType::STRING,
        sort: 1,
        fieldMetadata: []
    );

    $field = ExampleFlexyModel::getFieldsForSchema('test')->firstWhere('name', 'field_with_empty_metadata');
    expect($field->metadata)->toEqual([]);
});
