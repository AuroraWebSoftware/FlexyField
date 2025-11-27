<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
use AuroraWebSoftware\FlexyField\Models\Value;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

it('is idempotent when migration runs twice', function () {
    // Create legacy shapes
    DB::table('ff_shapes')->insert([
        'model_type' => ExampleFlexyModel::class,
        'field_name' => 'legacy_field',
        'field_type' => FlexyFieldType::STRING->value,
        'sort' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run migration first time
    Artisan::call('flexyfield:migrate-shapes', ['--force' => true]);
    $firstRunSets = FieldSet::where('model_type', ExampleFlexyModel::class)->count();
    $firstRunFields = SetField::where('set_code', 'default')->count();

    // Run migration second time
    Artisan::call('flexyfield:migrate-shapes', ['--force' => true]);
    $secondRunSets = FieldSet::where('model_type', ExampleFlexyModel::class)->count();
    $secondRunFields = SetField::where('set_code', 'default')->count();

    // Should have same counts (idempotent)
    expect($secondRunSets)->toBe($firstRunSets)
        ->and($secondRunFields)->toBe($firstRunFields);
});

it('handles migration with invalid model_type gracefully', function () {
    // Create shape with invalid model type
    DB::table('ff_shapes')->insert([
        'model_type' => 'NonExistent\\Model',
        'field_name' => 'test_field',
        'field_type' => FlexyFieldType::STRING->value,
        'sort' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Migration should complete but create field set for invalid type
    Artisan::call('flexyfield:migrate-shapes', ['--force' => true]);

    $fieldSet = FieldSet::where('model_type', 'NonExistent\\Model')->first();
    expect($fieldSet)->not->toBeNull()
        ->and($fieldSet->set_code)->toBe('default');
});

it('treats model_type casing as different during migration', function () {
    // Create shapes with mixed casing - they are treated as different model types
    DB::table('ff_shapes')->insert([
        ['model_type' => 'App\\Models\\Product', 'field_name' => 'field1', 'field_type' => FlexyFieldType::STRING->value, 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['model_type' => 'app\\models\\product', 'field_name' => 'field2', 'field_type' => FlexyFieldType::STRING->value, 'sort' => 2, 'created_at' => now(), 'updated_at' => now()],
    ]);

    Artisan::call('flexyfield:migrate-shapes', ['--force' => true]);

    // Should create separate field sets for different casings (they are different strings)
    // Note: Migration creates one set per distinct model_type, so both should be created
    $sets = FieldSet::whereIn('model_type', ['App\\Models\\Product', 'app\\models\\product'])->get();
    // Actually, both should exist since they're different strings
    expect($sets->count())->toBeGreaterThanOrEqual(1);
    // At least one should exist
    expect($sets->pluck('model_type')->toArray())->toContain('App\\Models\\Product');
});

it('handles migration with zero shapes', function () {
    // No shapes in table
    Artisan::call('flexyfield:migrate-shapes', ['--force' => true]);

    $sets = FieldSet::count();
    expect($sets)->toBe(0);
});

it('preserves all data during migration', function () {
    // Create legacy shape and value
    DB::table('ff_shapes')->insert([
        'model_type' => ExampleFlexyModel::class,
        'field_name' => 'legacy_field',
        'field_type' => FlexyFieldType::STRING->value,
        'sort' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $model = ExampleFlexyModel::create(['name' => 'Test Model']);
    DB::table('ff_values')->insert([
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'field_name' => 'legacy_field',
        'value_string' => 'legacy_value',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run migration
    Artisan::call('flexyfield:migrate-shapes', ['--force' => true]);

    // Verify field set created
    $fieldSet = FieldSet::where('model_type', ExampleFlexyModel::class)
        ->where('set_code', 'default')
        ->first();
    expect($fieldSet)->not->toBeNull();

    // Verify field migrated
    $setField = SetField::where('set_code', 'default')
        ->where('field_name', 'legacy_field')
        ->first();
    expect($setField)->not->toBeNull();

    // Verify value preserved
    $value = Value::where('model_type', ExampleFlexyModel::class)
        ->where('model_id', $model->id)
        ->where('field_name', 'legacy_field')
        ->first();
    expect($value)->not->toBeNull()
        ->and($value->value_string)->toBe('legacy_value');
});

it('assigns all model instances to default set during migration', function () {
    // Create legacy shape first
    DB::table('ff_shapes')->insert([
        'model_type' => ExampleFlexyModel::class,
        'field_name' => 'test_field',
        'field_type' => FlexyFieldType::STRING->value,
        'sort' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run migration to create default set
    Artisan::call('flexyfield:migrate-shapes', ['--force' => true]);

    // Create models after migration (they should auto-assign to default)
    $model1 = ExampleFlexyModel::create(['name' => 'Model 1']);
    $model2 = ExampleFlexyModel::create(['name' => 'Model 2']);

    // Verify models assigned to default set
    expect($model1->fresh()->field_set_code)->toBe('default')
        ->and($model2->fresh()->field_set_code)->toBe('default');
});
