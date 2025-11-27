<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetNotFoundException;
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

        $table->foreign('field_set_code')
            ->references('set_code')
            ->on('ff_field_sets')
            ->onDelete('set null')
            ->onUpdate('cascade');
    });
});

it('throws exception when assigning non-existent field set', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    expect(fn () => $model->assignToFieldSet('non_existent'))
        ->toThrow(\Exception::class);
});

it('throws FieldSetNotFoundException when assigning set from different model type', function () {
    // Create field set for different model type
    \AuroraWebSoftware\FlexyField\Models\FieldSet::create([
        'model_type' => 'App\\Models\\DifferentModel',
        'set_code' => 'different_set',
        'label' => 'Different Set',
    ]);

    $model = ExampleFlexyModel::create(['name' => 'Test']);

    expect(fn () => $model->assignToFieldSet('different_set'))
        ->toThrow(FieldSetNotFoundException::class);
});

it('allows assigning field set to unsaved model', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $model = new ExampleFlexyModel(['name' => 'New Model']);
    $model->assignToFieldSet('default');

    expect($model->getFieldSetCode())->toBe('default');
    expect($model->exists)->toBeTrue(); // Model is saved by assignToFieldSet
});

it('makes old values inaccessible when changing field set', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'set1',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'set2',
        fields: ['field2' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToFieldSet('set1');
    $model->flexy->field1 = 'value1';
    $model->save();

    expect($model->fresh()->flexy->field1)->toBe('value1');

    // Change to set2
    $model->assignToFieldSet('set2');
    $model->save();

    // field1 should be inaccessible (returns null)
    expect($model->fresh()->flexy->field1)->toBeNull();
    // field2 should be accessible
    $model->flexy->field2 = 'value2';
    $model->save();
    expect($model->fresh()->flexy->field2)->toBe('value2');
});

it('throws exception when assigning then deleting field set', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'temp',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToFieldSet('temp');

    // Delete the field set
    \AuroraWebSoftware\FlexyField\Models\FieldSet::where('set_code', 'temp')->delete();

    // Try to set a field
    $model->flexy->field1 = 'value';
    expect(fn () => $model->save())->toThrow(\Exception::class);
});

it('allows assigning same field set twice (idempotent)', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToFieldSet('default');

    expect($model->getFieldSetCode())->toBe('default');

    // Assign again
    $model->assignToFieldSet('default');

    expect($model->getFieldSetCode())->toBe('default');
});

it('returns empty collection when getAvailableFields called without assignment', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    $fields = $model->getAvailableFields();

    expect($fields)->toBeEmpty()
        ->and($fields->count())->toBe(0);
});
