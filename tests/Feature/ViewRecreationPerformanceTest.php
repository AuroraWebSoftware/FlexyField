<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesFieldSets;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

    // Create default field set - fields will be added dynamically in tests
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [],
        isDefault: true
    );
});

it('only recreates view when new fields are added', function () {
    // Add color field to set
    ExampleFlexyModel::addFieldToSet('default', 'color', FlexyFieldType::STRING);
    ExampleFlexyModel::addFieldToSet('default', 'size', FlexyFieldType::STRING);

    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    // First save - new field 'color', should recreate view
    $model->flexy->color = 'red';
    $model->save();

    // Check that field was added to schema tracking
    $colorExists = DB::table('ff_view_schema')
        ->where('field_name', 'color')
        ->exists();
    expect($colorExists)->toBeTrue();

    // Second save - same field 'color', should NOT recreate view
    $model->flexy->color = 'blue';
    $model->save();

    // Field should still exist in schema (not duplicated)
    $colorCount = DB::table('ff_view_schema')
        ->where('field_name', 'color')
        ->count();
    expect($colorCount)->toBe(1);

    // Third save - new field 'size', should recreate view
    $model->flexy->size = 'L';
    $model->save();

    // Both fields should exist in schema
    $totalFields = DB::table('ff_view_schema')->count();
    expect($totalFields)->toBe(2);
});

it('handles bulk updates efficiently', function () {
    // Add status field to set
    ExampleFlexyModel::addFieldToSet('default', 'status', FlexyFieldType::STRING);

    // Create 100 models and set the same field
    for ($i = 1; $i <= 100; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Model $i"]);
        $model->flexy->status = 'active';
        $model->save();
    }

    // View should only be recreated once (first time 'status' field was seen)
    $schemaCount = DB::table('ff_view_schema')->count();
    expect($schemaCount)->toBe(1);
});

it('tracks multiple different fields correctly', function () {
    // Add fields to set
    ExampleFlexyModel::addFieldToSet('default', 'field1', FlexyFieldType::STRING);
    ExampleFlexyModel::addFieldToSet('default', 'field2', FlexyFieldType::STRING);
    ExampleFlexyModel::addFieldToSet('default', 'field3', FlexyFieldType::STRING);

    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    // Add multiple different fields
    $model->flexy->field1 = 'value1';
    $model->save();

    $model->flexy->field2 = 'value2';
    $model->save();

    $model->flexy->field3 = 'value3';
    $model->save();

    // All three fields should be tracked
    $totalFields = DB::table('ff_view_schema')->count();
    expect($totalFields)->toBe(3);

    $fields = DB::table('ff_view_schema')
        ->pluck('field_name')
        ->toArray();

    expect($fields)->toContain('field1')
        ->and($fields)->toContain('field2')
        ->and($fields)->toContain('field3');
});

it('forceRecreateView rebuilds schema tracking from actual data', function () {
    // Add fields to set
    ExampleFlexyModel::addFieldToSet('default', 'color', FlexyFieldType::STRING);
    ExampleFlexyModel::addFieldToSet('default', 'size', FlexyFieldType::STRING);

    // Create some models with fields
    $model1 = ExampleFlexyModel::create(['name' => 'Model 1']);
    $model1->flexy->color = 'red';
    $model1->flexy->size = 'L';
    $model1->save();

    // Manually corrupt schema tracking
    DB::table('ff_view_schema')->truncate();
    expect(DB::table('ff_view_schema')->count())->toBe(0);

    // Force recreate should rebuild from ff_values
    FlexyField::forceRecreateView();

    $schemaCount = DB::table('ff_view_schema')->count();
    expect($schemaCount)->toBe(2);

    $fields = DB::table('ff_view_schema')
        ->pluck('field_name')
        ->toArray();

    expect($fields)->toContain('color')
        ->and($fields)->toContain('size');
});

it('recreateViewIfNeeded returns false when no new fields', function () {
    // Add color field to set
    ExampleFlexyModel::addFieldToSet('default', 'color', FlexyFieldType::STRING);

    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    // First save - new field
    $model->flexy->color = 'red';
    $model->save();

    // Directly test recreateViewIfNeeded with existing field
    $wasRecreated = FlexyField::recreateViewIfNeeded(['color']);
    expect($wasRecreated)->toBeFalse();
});

it('recreateViewIfNeeded returns true when new fields detected', function () {
    // Directly test with a new field
    $wasRecreated = FlexyField::recreateViewIfNeeded(['brand_new_field']);
    expect($wasRecreated)->toBeTrue();

    // Verify field was tracked
    $exists = DB::table('ff_view_schema')
        ->where('field_name', 'brand_new_field')
        ->exists();
    expect($exists)->toBeTrue();
});

it('handles empty field names array', function () {
    $wasRecreated = FlexyField::recreateViewIfNeeded([]);
    expect($wasRecreated)->toBeFalse();
});
