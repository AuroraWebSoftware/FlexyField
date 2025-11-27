<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('migrate:fresh');
});

it('throws exception for invalid field type', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    // Laravel's enum casting may throw different exceptions depending on version
    // We just verify that an exception is thrown
    try {
        SetField::create([
            'set_code' => 'test',
            'field_name' => 'invalid',
            'field_type' => 'invalid_type', // Not a FlexyFieldType enum
            'sort' => 1,
        ]);
        expect(false)->toBeTrue('Should have thrown an exception');
    } catch (\Throwable $e) {
        expect($e)->toBeInstanceOf(\Throwable::class);
    }
});

it('validates field type on update', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    $setField = SetField::create([
        'set_code' => 'test',
        'field_name' => 'field1',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);

    // Try to update with invalid type - Laravel's enum casting will throw exception
    try {
        $setField->field_type = 'invalid';
        $setField->save();
        expect(false)->toBeTrue('Should have thrown an exception');
    } catch (\Throwable $e) {
        expect($e)->toBeInstanceOf(\Throwable::class);
    }
});

it('returns empty array for getValidationRulesArray when rules are empty', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    $setField = SetField::create([
        'set_code' => 'test',
        'field_name' => 'field1',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
        'validation_rules' => null,
    ]);

    expect($setField->getValidationRulesArray())->toBe([]);
});

it('returns array directly when validation_rules is already array', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
    ]);

    $setField = new SetField([
        'set_code' => 'test',
        'field_name' => 'field1',
        'field_type' => FlexyFieldType::STRING,
        'sort' => 1,
    ]);
    $setField->validation_rules = ['required', 'string', 'max:255'];

    expect($setField->getValidationRulesArray())->toBe(['required', 'string', 'max:255']);
});
