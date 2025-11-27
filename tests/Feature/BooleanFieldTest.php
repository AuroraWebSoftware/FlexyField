<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Models\Value;
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

    // Create default field set with all boolean test fields
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'is_active' => ['type' => FlexyFieldType::BOOLEAN],
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
            'int_field' => ['type' => FlexyFieldType::INTEGER],
            'is_verified' => ['type' => FlexyFieldType::BOOLEAN],
            'is_premium' => ['type' => FlexyFieldType::BOOLEAN],
        ],
        isDefault: true
    );

    // Create initial empty view
    FlexyField::dropAndCreatePivotView();
});

it('can store and retrieve boolean false value', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->is_active = false;
    $model->save();

    $fresh = ExampleFlexyModel::find($model->id);

    expect($fresh->flexy->is_active)->toBe(false)
        ->and($fresh->flexy->is_active)->not->toBeNull();
});

it('can store and retrieve boolean true value', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->is_active = true;
    $model->save();

    $fresh = ExampleFlexyModel::find($model->id);

    expect($fresh->flexy->is_active)->toBe(true)
        ->and($fresh->flexy->is_active)->not->toBeNull();
});

it('can query models by boolean false value', function () {
    $model1 = ExampleFlexyModel::create(['name' => 'Inactive']);
    $model1->flexy->is_active = false;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Active']);
    $model2->flexy->is_active = true;
    $model2->save();

    // Recreate view to include the new field
    FlexyField::forceRecreateView();

    $inactive = ExampleFlexyModel::where('flexy_is_active', false)->get();

    expect($inactive)->toHaveCount(1)
        ->and($inactive->first()->name)->toBe('Inactive');
});

it('can query models by boolean true value', function () {
    $model1 = ExampleFlexyModel::create(['name' => 'Inactive']);
    $model1->flexy->is_active = false;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Active']);
    $model2->flexy->is_active = true;
    $model2->save();

    // Recreate view to include the new field
    FlexyField::forceRecreateView();

    $active = ExampleFlexyModel::where('flexy_is_active', true)->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->name)->toBe('Active');
});

it('distinguishes boolean false from integer 0 in storage', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->bool_field = false;
    $model->flexy->int_field = 0;
    $model->save();

    $boolValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'bool_field')
        ->first();

    $intValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'int_field')
        ->first();

    expect($boolValue->value_boolean)->toBe(false)
        ->and($boolValue->value_int)->toBeNull()
        ->and($intValue->value_int)->toBe(0)
        ->and($intValue->value_boolean)->toBeNull();
});

it('distinguishes boolean true from integer 1 in storage', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->bool_field = true;
    $model->flexy->int_field = 1;
    $model->save();

    $boolValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'bool_field')
        ->first();

    $intValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'int_field')
        ->first();

    expect($boolValue->value_boolean)->toBe(true)
        ->and($boolValue->value_int)->toBeNull()
        ->and($intValue->value_int)->toBe(1)
        ->and($intValue->value_boolean)->toBeNull();
});

it('can validate boolean fields with field sets', function () {
    // Check if field already exists, if not add it
    $existingField = \AuroraWebSoftware\FlexyField\Models\SetField::where('set_code', 'default')
        ->where('field_name', 'is_verified')
        ->first();

    if (! $existingField) {
        ExampleFlexyModel::addFieldToSet('default', 'is_verified', FlexyFieldType::BOOLEAN, 1, 'required|boolean');
    }

    $model = ExampleFlexyModel::create(['name' => 'Test Model']);
    $model->flexy->is_verified = true;
    $model->save();

    expect($model->fresh()->flexy->is_verified)->toBe(true);
});

it('can update boolean value from false to true', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->is_active = false;
    $model->save();
    expect($model->fresh()->flexy->is_active)->toBe(false);

    $model->flexy->is_active = true;
    $model->save();
    expect($model->fresh()->flexy->is_active)->toBe(true);
});

it('can update boolean value from true to false', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->is_active = true;
    $model->save();
    expect($model->fresh()->flexy->is_active)->toBe(true);

    $model->flexy->is_active = false;
    $model->save();
    expect($model->fresh()->flexy->is_active)->toBe(false);
});

it('handles multiple boolean fields on same model', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->is_active = true;
    $model->flexy->is_verified = false;
    $model->flexy->is_premium = true;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->is_active)->toBe(true)
        ->and($fresh->flexy->is_verified)->toBe(false)
        ->and($fresh->flexy->is_premium)->toBe(true);
});
