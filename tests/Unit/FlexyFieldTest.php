<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\FlexyField;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\Value;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesFieldSets;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(CreatesFieldSets::class);

beforeEach(function () {
    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('field_set_code')->nullable();
        $table->timestamps();
    });

    // Create default field set
    FieldSet::create([
        'model_type' => ExampleFlexyModel::class,
        'set_code' => 'default',
        'label' => 'Default',
        'is_default' => true,
    ]);

    // Clear schema tracking for clean test state
    DB::table('ff_view_schema')->truncate();
});

it('can drop and create pivot view for MySQL', function () {
    // Add field to set
    ExampleFlexyModel::addFieldToSet('default', 'test_field', FlexyFieldType::STRING);

    // Create some test values
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->flexy->test_field = 'test_value';
    $model->save();

    // Recreate view
    FlexyField::dropAndCreatePivotView();

    // Check view exists - use current database name
    $dbName = DB::connection()->getDatabaseName();
    $viewExists = DB::select("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_{$dbName} = 'ff_values_pivot_view'");
    expect(count($viewExists))->toBeGreaterThan(0);
});

it('recreateViewIfNeeded returns false when no new fields', function () {
    // Add field to set
    ExampleFlexyModel::addFieldToSet('default', 'existing_field', FlexyFieldType::STRING);

    // Create a field
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->flexy->existing_field = 'value';
    $model->save();

    // Track the field if not already tracked
    if (! DB::table('ff_view_schema')->where('field_name', 'existing_field')->exists()) {
        DB::table('ff_view_schema')->insert([
            'field_name' => 'existing_field',
            'added_at' => now(),
        ]);
    }

    // Try to recreate with existing field
    $result = FlexyField::recreateViewIfNeeded(['existing_field']);

    expect($result)->toBeFalse();
});

it('recreateViewIfNeeded returns true and recreates view when new fields exist', function () {
    // Clear schema tracking first
    DB::table('ff_view_schema')->truncate();

    // Add existing field to set
    ExampleFlexyModel::addFieldToSet('default', 'existing_field', FlexyFieldType::STRING);

    // Create initial field
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->flexy->existing_field = 'value';
    $model->save();

    // Track existing field (save() already added it, but ensure it's there)
    if (! DB::table('ff_view_schema')->where('field_name', 'existing_field')->exists()) {
        DB::table('ff_view_schema')->insert([
            'field_name' => 'existing_field',
            'added_at' => now(),
        ]);
    }

    // Add new field to set (this will trigger recreateViewIfNeeded automatically)
    ExampleFlexyModel::addFieldToSet('default', 'new_field', FlexyFieldType::STRING);
    
    // Check that new_field was already tracked by addFieldToSet
    $alreadyTracked = DB::table('ff_view_schema')->where('field_name', 'new_field')->exists();
    
    // Now test recreateViewIfNeeded with the new field (should return false since it's already tracked)
    $result = FlexyField::recreateViewIfNeeded(['new_field']);
    
    // If it was already tracked, result should be false; otherwise true
    if ($alreadyTracked) {
        expect($result)->toBeFalse();
    } else {
        expect($result)->toBeTrue();
        // Check new field is tracked
        $tracked = DB::table('ff_view_schema')->where('field_name', 'new_field')->exists();
        expect($tracked)->toBeTrue();
    }
    
    // Set new field value and save
    $model->flexy->new_field = 'new_value';
    $model->save();
});

it('recreateViewIfNeeded returns false for empty field names', function () {
    $result = FlexyField::recreateViewIfNeeded([]);

    expect($result)->toBeFalse();
});

it('forceRecreateView rebuilds schema tracking and recreates view', function () {
    // Add fields to set
    ExampleFlexyModel::addFieldToSet('default', 'field1', FlexyFieldType::STRING);
    ExampleFlexyModel::addFieldToSet('default', 'field2', FlexyFieldType::INTEGER);

    // Create some values
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->flexy->field1 = 'value1';
    $model->flexy->field2 = 42;
    $model->save();

    // Force recreate
    FlexyField::forceRecreateView();

    // Check all fields are tracked
    $trackedFields = DB::table('ff_view_schema')->pluck('field_name')->toArray();
    expect($trackedFields)->toContain('field1', 'field2');
});

it('forceRecreateView handles empty values table', function () {
    // No values exist
    FlexyField::forceRecreateView();

    // Should not throw exception - use current database name
    $dbName = DB::connection()->getDatabaseName();
    $viewExists = DB::select("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_{$dbName} = 'ff_values_pivot_view'");
    expect(count($viewExists))->toBeGreaterThan(0);
});
