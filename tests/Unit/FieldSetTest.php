<?php

use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

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

it('can create a field set', function () {
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'shoes',
        'label' => 'Shoes',
        'description' => 'Fields for shoe products',
        'is_default' => false,
    ]);

    expect($fieldSet)->toBeInstanceOf(FieldSet::class)
        ->and($fieldSet->model_type)->toBe(ExampleFlexyModel::class)
        ->and($fieldSet->set_code)->toBe('shoes')
        ->and($fieldSet->label)->toBe('Shoes')
        ->and($fieldSet->is_default)->toBeFalse();
});

it('enforces only one default field set per model type', function () {
    // Create first default
    $fieldSet1 = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default1',
        'label' => 'Default 1',
        'is_default' => true,
    ]);

    expect($fieldSet1->is_default)->toBeTrue();

    // Create second default - should make first non-default
    $fieldSet2 = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default2',
        'label' => 'Default 2',
        'is_default' => true,
    ]);

    expect($fieldSet2->fresh()->is_default)->toBeTrue()
        ->and($fieldSet1->fresh()->is_default)->toBeFalse();
});

it('can store metadata as JSON', function () {
    $metadata = ['category' => 'apparel', 'priority' => 1];

    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'test',
        'label' => 'Test',
        'metadata' => $metadata,
    ]);

    expect($fieldSet->fresh()->metadata)->toBe($metadata);
});

it('has fields relationship', function () {
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'size',
        'field_type' => 'string',
        'sort' => 1,
    ]);

    SetField::create([
        'set_code' => 'shoes',
        'field_name' => 'color',
        'field_type' => 'string',
        'sort' => 2,
    ]);

    expect($fieldSet->fields)->toHaveCount(2)
        ->and($fieldSet->fields->pluck('field_name')->toArray())->toBe(['size', 'color']);
});

it('can scope by model type', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'set1',
        'label' => 'Set 1',
    ]);

    FieldSet::create([
        'model_type' => 'App\\Models\\Product',
        'set_code' => 'set2',
        'label' => 'Set 2',
    ]);

    $sets = FieldSet::forModel(ExampleFlexyModel::class)->get();

    expect($sets)->toHaveCount(1)
        ->and($sets->first()->set_code)->toBe('set1');
});

it('can get default field set for model type', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'non_default',
        'label' => 'Non Default',
        'is_default' => false,
    ]);

    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default',
        'label' => 'Default',
        'is_default' => true,
    ]);

    $defaultSet = FieldSet::default(ExampleFlexyModel::class)->first();

    expect($defaultSet->set_code)->toBe('default')
        ->and($defaultSet->is_default)->toBeTrue();
});

it('can check usage count', function () {
    $fieldSet = FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    // Create 3 models assigned to this field set
    for ($i = 1; $i <= 3; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Model {$i}"]);
        $model->field_set_code = 'shoes';
        $model->save();
    }

    expect($fieldSet->getUsageCount(ExampleFlexyModel::class))->toBe(3)
        ->and($fieldSet->isInUse(ExampleFlexyModel::class))->toBeTrue();
});

it('enforces unique constraint on model_type and set_code', function () {
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'duplicate',
        'label' => 'First',
    ]);

    expect(fn () => FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'duplicate',
        'label' => 'Second',
    ]))->toThrow(\Exception::class);
});
