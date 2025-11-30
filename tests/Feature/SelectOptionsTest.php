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

// Single Select Tests
it('validates single select with indexed array options', function () {
    // Create schema with single select field
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'size',
        FlexyFieldType::STRING,
        100,
        null,
        null,
        ['options' => ['S', 'M', 'L', 'XL']]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Valid value
    $model->flexy->size = 'M';
    $model->save();

    $model->refresh();
    expect($model->flexy->size)->toBe('M');
});

it('validates single select with associative array options', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'color',
        FlexyFieldType::STRING,
        100, null, null, ['options' => ['r' => 'Red', 'b' => 'Blue', 'g' => 'Green']]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Valid value using key
    $model->flexy->color = 'r';
    $model->save();

    $model->refresh();
    expect($model->flexy->color)->toBe('r');
});

it('rejects invalid single select value', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'size',
        FlexyFieldType::STRING,
        100, null, null, ['options' => ['S', 'M', 'L']]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Invalid value
    $model->flexy->size = 'XL'; // Not in options

    expect(fn () => $model->save())->toThrow(\Illuminate\Validation\ValidationException::class);
});

// Multi-Select Tests
it('validates multi-select with indexed array options', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'features',
        FlexyFieldType::JSON,
        100, null, null, [
            'options' => ['wifi', 'bluetooth', '5g', 'nfc'],
            'multiple' => true,
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Valid array of values
    $model->flexy->features = ['wifi', '5g'];
    $model->save();

    $model->refresh();
    expect($model->flexy->features)->toBe(['wifi', '5g']);
});

it('validates multi-select with associative array options', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'tags',
        FlexyFieldType::JSON,
        100, null, null, [
            'options' => ['new' => 'New Arrival', 'sale' => 'On Sale', 'feat' => 'Featured'],
            'multiple' => true,
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Valid array using keys
    $model->flexy->tags = ['new', 'sale'];
    $model->save();

    $model->refresh();
    expect($model->flexy->tags)->toBe(['new', 'sale']);
});

it('rejects invalid multi-select value (not array)', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'features',
        FlexyFieldType::JSON,
        100, null, null, [
            'options' => ['wifi', 'bluetooth'],
            'multiple' => true,
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Invalid: not an array
    $model->flexy->features = 'wifi';

    expect(fn () => $model->save())->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('rejects multi-select with invalid option', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'features',
        FlexyFieldType::JSON,
        100, null, null, [
            'options' => ['wifi', 'bluetooth'],
            'multiple' => true,
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Invalid: contains value not in options
    $model->flexy->features = ['wifi', '5g']; // '5g' not in options

    expect(fn () => $model->save())->toThrow(\Illuminate\Validation\ValidationException::class);
});

// Edge Cases
it('allows fields without options to accept any value', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'description',
        FlexyFieldType::STRING
        // No options metadata
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Any value should be accepted
    $model->flexy->description = 'Any text here';
    $model->save();

    $model->refresh();
    expect($model->flexy->description)->toBe('Any text here');
});

it('handles empty options array gracefully', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'status',
        FlexyFieldType::STRING,
        100, null, null, ['options' => []]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Empty options should behave like no options
    $model->flexy->status = 'active';
    $model->save();

    $model->refresh();
    expect($model->flexy->status)->toBe('active');
});

it('allows empty array for multi-select', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'features',
        FlexyFieldType::JSON,
        100, null, null, [
            'options' => ['wifi', 'bluetooth'],
            'multiple' => true,
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Empty array should be valid
    $model->flexy->features = [];
    $model->save();

    $model->refresh();
    expect($model->flexy->features)->toBe([]);
});

it('combines options validation with other validation rules', function () {
    ExampleFlexyModel::createSchema('test', 'Test Schema');
    ExampleFlexyModel::addFieldToSchema(
        'test',
        'priority',
        FlexyFieldType::STRING,
        100,
        'required',
        null,
        ['options' => ['low', 'medium', 'high']]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test required + options - setting empty value should fail
    $model->flexy->priority = '';
    expect(fn () => $model->save())->toThrow(\Illuminate\Validation\ValidationException::class);

    // Test invalid option - should fail
    $model->flexy->priority = 'urgent'; // not in options
    expect(fn () => $model->save())->toThrow(\Illuminate\Validation\ValidationException::class);

    // Valid value should succeed
    $model->flexy->priority = 'medium';
    $model->save();

    $model->refresh();
    expect($model->flexy->priority)->toBe('medium');
});
