<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSetException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetNotFoundException;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
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

it('can assign a model instance to a field set', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: [
            'size' => ['type' => FlexyFieldType::STRING],
            'color' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Shoe Product']);
    $model->assignToFieldSet('footwear');

    expect($model->getFieldSetCode())->toBe('footwear')
        ->and($model->fresh()->field_set_code)->toBe('footwear');
});

it('can get field set code for an instance', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'books',
        fields: ['isbn' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $model = ExampleFlexyModel::create(['name' => 'Book']);
    expect($model->getFieldSetCode())->toBeNull();

    $model->assignToFieldSet('books');
    expect($model->getFieldSetCode())->toBe('books');
});

it('can get available fields for an instance', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: [
            'size' => ['type' => FlexyFieldType::STRING, 'sort' => 1],
            'color' => ['type' => FlexyFieldType::STRING, 'sort' => 2],
            'material' => ['type' => FlexyFieldType::STRING, 'sort' => 3],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Shoe']);
    $model->assignToFieldSet('footwear');

    $availableFields = $model->getAvailableFields();

    expect($availableFields)->toHaveCount(3)
        ->and($availableFields->pluck('field_name')->toArray())
        ->toBe(['size', 'color', 'material']);
});

it('restricts field access to assigned field set', function () {
    // Create two field sets with different fields
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]]
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'books',
        fields: ['isbn' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('footwear');

    // Should be able to set field from assigned set
    $model->flexy->size = '42';
    $model->save();

    expect($model->fresh()->flexy->size)->toBe('42');

    // Should NOT be able to set field from different set
    $model->flexy->isbn = '1234567890';
    expect(fn () => $model->save())->toThrow(FieldNotInSetException::class);
});

it('throws FieldSetNotFoundException when accessing fields without assignment', function () {
    $model = ExampleFlexyModel::create(['name' => 'Product']);

    // Try to set a field without field set assignment
    $model->flexy->test_field = 'value';
    expect(fn () => $model->save())->toThrow(FieldSetNotFoundException::class);
});

it('can change field set assignment', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]]
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'books',
        fields: ['isbn' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('footwear');
    $model->flexy->size = '42';
    $model->save();

    expect($model->getFieldSetCode())->toBe('footwear')
        ->and($model->fresh()->flexy->size)->toBe('42');

    // Change to books field set
    $model->assignToFieldSet('books');
    $model->save();

    expect($model->getFieldSetCode())->toBe('books')
        ->and($model->fresh()->flexy->size)->toBeNull() // Old field no longer accessible
        ->and($model->getAvailableFields()->pluck('field_name')->toArray())
        ->toContain('isbn');
});

it('can assign field set to unsaved model', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['test_field' => ['type' => FlexyFieldType::STRING]]
    );

    $model = new ExampleFlexyModel(['name' => 'New Product']);
    $model->assignToFieldSet('default');

    expect($model->getFieldSetCode())->toBe('default');

    // Should be able to save
    $model->save();
    expect($model->fresh()->field_set_code)->toBe('default');
});

it('can use fieldSet relationship for eager loading', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Shoe']);
    $model->assignToFieldSet('footwear');
    $model->save();

    $loaded = ExampleFlexyModel::with('fieldSet')->find($model->id);

    expect($loaded->fieldSet)->toBeInstanceOf(FieldSet::class)
        ->and($loaded->fieldSet->set_code)->toBe('footwear');
});
