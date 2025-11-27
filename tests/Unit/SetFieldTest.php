<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('migrate:fresh');
});

it('can create a set field', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    $setField = SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'size',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_rules' => 'required|string',
    ]);

    expect($setField)->toBeInstanceOf(SetField::class)
        ->and($setField->set_code)->toBe('shoes')
        ->and($setField->field_name)->toBe('size')
        ->and($setField->field_type)->toBe(FlexyFieldType::STRING)
        ->and($setField->validation_rules)->toBe('required|string');
});

it('casts field_type to enum', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    $setField = SetField::create([
        'set_code' => 'test',
        'field_name' => 'count',
        'field_type' => FlexyFieldType::INTEGER,
        'sort' => 1,
    ]);

    expect($setField->field_type)->toBeInstanceOf(FlexyFieldType::class)
        ->and($setField->field_type)->toBe(FlexyFieldType::INTEGER);
});

it('stores validation messages as JSON', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    $messages = ['required' => 'This field is required'];

    $setField = SetField::create([
        'set_code' => 'test',
        'field_name' => 'email',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_messages' => $messages,
    ]);

    expect($setField->fresh()->validation_messages)->toBe($messages);
});

it('stores field metadata as JSON', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    $metadata = ['placeholder' => 'Enter size', 'help_text' => 'US sizing'];

    $setField = SetField::create([
        'set_code' => 'test',
        'field_name' => 'size',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
        'field_metadata' => $metadata,
    ]);

    expect($setField->fresh()->field_metadata)->toEqual($metadata);
});

it('has fieldSet relationship', function () {
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    $setField = SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'size',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    expect($setField->fieldSet)->toBeInstanceOf(FieldSet::class)
        ->and($setField->fieldSet->set_code)->toBe('shoes');
});

it('gets validation rules as array', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    $setField = SetField::create([
        'set_code' => 'test',
        'field_name' => 'email',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_rules' => 'required|email|max:255',
    ]);

    $rulesArray = $setField->getValidationRulesArray();

    expect($rulesArray)->toBeArray()
        ->and($rulesArray)->toBe(['required', 'email', 'max:255']);
});

it('enforces unique constraint on set_code and field_name', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'size',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    expect(fn () => SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'size',
        'field_type' => FlexyFieldType::INTEGER,
        'sort' => 2,
    ]))->toThrow(\Exception::class);
});

it('cascades delete when field set is deleted', function () {
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'size',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'color',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 2,
    ]);

    expect(SetField::where('set_code', 'shoes')->count())->toBe(2);

    $fieldSet->delete();

    expect(SetField::where('set_code', 'shoes')->count())->toBe(0);
});
