<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesFieldSets;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(CreatesFieldSets::class);

beforeEach(function () {
    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('field_set_code')->nullable()->index();
        $table->timestamps();

// REMOVED FOR PGSQL:         $table->foreign('field_set_code')
// REMOVED FOR PGSQL:             ->references('set_code')
// REMOVED FOR PGSQL:             ->on('ff_field_sets')
// REMOVED FOR PGSQL:             ->onDelete('set null')
// REMOVED FOR PGSQL:             ->onUpdate('cascade');
    });
});

it('can get model type', function () {
    expect(ExampleFlexyModel::getModelType())->toBe(ExampleFlexyModel::class);
});

it('can create field set with all parameters', function () {
    $fieldSet = ExampleFlexyModel::createFieldSet(
        'test_set',
        'Test Set',
        'Test description',
        ['key' => 'value'],
        false
    );

    expect($fieldSet)->toBeInstanceOf(FieldSet::class)
        ->and($fieldSet->set_code)->toBe('test_set')
        ->and($fieldSet->label)->toBe('Test Set')
        ->and($fieldSet->description)->toBe('Test description')
        ->and($fieldSet->metadata)->toBe(['key' => 'value'])
        ->and($fieldSet->is_default)->toBeFalse();
});

it('can get field set by code', function () {
    $fieldSet = ExampleFlexyModel::createFieldSet('test', 'Test', null, null, false);

    $retrieved = ExampleFlexyModel::getFieldSet('test');

    expect($retrieved)->toBeInstanceOf(FieldSet::class)
        ->and($retrieved->set_code)->toBe('test');
});

it('can get all field sets for model type', function () {
    ExampleFlexyModel::createFieldSet('set1', 'Set 1', null, null, false);
    ExampleFlexyModel::createFieldSet('set2', 'Set 2', null, null, false);
    ExampleFlexyModel::createFieldSet('set3', 'Set 3', null, null, false);

    $allSets = ExampleFlexyModel::getAllFieldSets();

    expect($allSets)->toHaveCount(3)
        ->and($allSets->pluck('set_code')->toArray())->toContain('set1', 'set2', 'set3');
});

it('can delete field set when not in use', function () {
    $fieldSet = ExampleFlexyModel::createFieldSet('temp', 'Temp', null, null, false);

    $result = ExampleFlexyModel::deleteFieldSet('temp');

    expect($result)->toBeTrue()
        ->and(FieldSet::where('set_code', 'temp')->exists())->toBeFalse();
});

it('can add field to set with all parameters', function () {
    ExampleFlexyModel::createFieldSet('test', 'Test', null, null, false);

    $setField = ExampleFlexyModel::addFieldToSet(
        'test',
        'test_field',
        FlexyFieldType::STRING,
        10,
        'required|string|max:255',
        ['required' => 'Field is required'],
        ['placeholder' => 'Enter value']
    );

    expect($setField)->toBeInstanceOf(SetField::class)
        ->and($setField->set_code)->toBe('test')
        ->and($setField->field_name)->toBe('test_field')
        ->and($setField->field_type)->toBe(FlexyFieldType::STRING)
        ->and($setField->sort)->toBe(10)
        ->and($setField->validation_rules)->toBe('required|string|max:255')
        ->and($setField->validation_messages)->toBe(['required' => 'Field is required'])
        ->and($setField->field_metadata)->toBe(['placeholder' => 'Enter value']);
});

it('can remove field from set', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'test',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $result = ExampleFlexyModel::removeFieldFromSet('test', 'field1');

    expect($result)->toBeTrue()
        ->and(SetField::where('set_code', 'test')->where('field_name', 'field1')->exists())->toBeFalse();
});

it('can get fields for set', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING, 'sort' => 1],
            'field2' => ['type' => FlexyFieldType::INTEGER, 'sort' => 2],
            'field3' => ['type' => FlexyFieldType::BOOLEAN, 'sort' => 3],
        ]
    );

    $fields = ExampleFlexyModel::getFieldsForSet('test');

    expect($fields)->toHaveCount(3)
        ->and($fields->pluck('field_name')->toArray())->toBe(['field1', 'field2', 'field3']);
});

it('can use query scopes', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'set1',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'set2',
        fields: ['field2' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $model1 = ExampleFlexyModel::create(['name' => 'Model 1']);
    $model1->assignToFieldSet('set1');

    $model2 = ExampleFlexyModel::create(['name' => 'Model 2']);
    $model2->assignToFieldSet('set2');

    $model3 = ExampleFlexyModel::create(['name' => 'Model 3']);
    // No field set assigned

    // Test whereFieldSet
    $set1Models = ExampleFlexyModel::whereFieldSet('set1')->get();
    expect($set1Models)->toHaveCount(1)
        ->and($set1Models->first()->name)->toBe('Model 1');

    // Test whereFieldSetIn
    $setModels = ExampleFlexyModel::whereFieldSetIn(['set1', 'set2'])->get();
    expect($setModels)->toHaveCount(2);

    // Test whereFieldSetNull
    $nullModels = ExampleFlexyModel::whereFieldSetNull()->get();
    expect($nullModels)->toHaveCount(1)
        ->and($nullModels->first()->name)->toBe('Model 3');
});

it('handles model deletion and cleans up values', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->flexy->field1 = 'value';
    $model->save();

    $modelId = $model->id;
    expect(\AuroraWebSoftware\FlexyField\Models\Value::where('model_id', $modelId)->count())->toBe(1);

    $model->delete();

    expect(\AuroraWebSoftware\FlexyField\Models\Value::where('model_id', $modelId)->count())->toBe(0);
});

it('auto-assigns default field set on model creation', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]],
        isDefault: true
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);

    expect($model->getFieldSetCode())->toBe('default');
});

it('does not auto-assign when field_set_code is explicitly set', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]],
        isDefault: true
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'custom',
        fields: ['field2' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $model = new ExampleFlexyModel(['name' => 'Test']);
    $model->field_set_code = 'custom';
    $model->save();

    expect($model->getFieldSetCode())->toBe('custom');
});
