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

// REMOVED FOR PGSQL:         $table->foreign('field_set_code')
// REMOVED FOR PGSQL:             ->references('set_code')
// REMOVED FOR PGSQL:             ->on('ff_field_sets')
// REMOVED FOR PGSQL:             ->onDelete('set null')
// REMOVED FOR PGSQL:             ->onUpdate('cascade');
    });
});

it('creates field set with 100 fields in acceptable time', function () {
    $startTime = microtime(true);

    $fields = [];
    for ($i = 1; $i <= 100; $i++) {
        $fields["field_{$i}"] = ['type' => FlexyFieldType::STRING];
    }

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'large_set',
        fields: $fields,
        isDefault: false
    );

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Should complete in less than 2 minutes (120 seconds)
    expect($duration)->toBeLessThan(120.0);

    // Verify all fields created
    $fieldCount = \AuroraWebSoftware\FlexyField\Models\SetField::where('set_code', 'large_set')->count();
    expect($fieldCount)->toBe(100);
})->skip('Performance test - may be slow');

it('handles field set change on many models efficiently', function () {
    // Create two field sets
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

    // Create 1000 models with set1
    $models = [];
    for ($i = 1; $i <= 1000; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Model {$i}"]);
        $model->field_set_code = 'set1';
        $model->save();
        $models[] = $model;
    }

    // Change all to set2
    $startTime = microtime(true);

    DB::table('ff_example_flexy_models')
        ->whereIn('id', collect($models)->pluck('id'))
        ->update(['field_set_code' => 'set2']);

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Should complete in less than 5 seconds
    expect($duration)->toBeLessThan(5.0);

    // Verify all changed
    $changedCount = ExampleFlexyModel::whereFieldSet('set2')->count();
    expect($changedCount)->toBe(1000);
})->skip('Performance test - may be slow');

it('recreates pivot view efficiently with multiple field sets', function () {
    // Create 10 field sets with 20 fields each
    for ($setNum = 1; $setNum <= 10; $setNum++) {
        $fields = [];
        for ($fieldNum = 1; $fieldNum <= 20; $fieldNum++) {
            $fields["field_{$setNum}_{$fieldNum}"] = ['type' => FlexyFieldType::STRING];
        }

        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: "set{$setNum}",
            fields: $fields,
            isDefault: false
        );
    }

    $startTime = microtime(true);

    // Force recreate view
    FlexyField::forceRecreateView();

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Should complete in less than 10 seconds
    expect($duration)->toBeLessThan(10.0);
})->skip('Performance test - may be slow');

it('queries efficiently with 100 field sets', function () {
    // Create 100 field sets
    for ($i = 1; $i <= 100; $i++) {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: "set{$i}",
            fields: ['field1' => ['type' => FlexyFieldType::STRING]],
            isDefault: false
        );
    }

    // Create models across different sets
    for ($i = 1; $i <= 50; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Model {$i}"]);
        $model->field_set_code = 'set'.($i % 100 + 1);
        $model->save();
    }

    $startTime = microtime(true);

    // Query with whereFieldSetIn
    $results = ExampleFlexyModel::whereFieldSetIn(
        collect(range(1, 50))->map(fn ($i) => "set{$i}")->toArray()
    )->get();

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Should complete in less than 1 second
    expect($duration)->toBeLessThan(1.0);
    expect($results)->toHaveCount(50);
})->skip('Performance test - may be slow');

it('handles concurrent pivot view recreation gracefully', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'test',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    // Simulate concurrent recreation attempts
    $errors = 0;
    for ($i = 0; $i < 5; $i++) {
        try {
            FlexyField::forceRecreateView();
        } catch (\Exception $e) {
            $errors++;
        }
    }

    // Should handle gracefully (may have some errors but shouldn't crash)
    expect($errors)->toBeLessThan(5);
});

it('efficiently queries models with field set filtering', function () {
    // Create 10 field sets
    for ($i = 1; $i <= 10; $i++) {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: "set{$i}",
            fields: ['field1' => ['type' => FlexyFieldType::STRING]],
            isDefault: false
        );
    }

    // Create 1000 models distributed across sets
    for ($i = 1; $i <= 1000; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Model {$i}"]);
        $model->field_set_code = 'set'.(($i % 10) + 1);
        $model->save();
    }

    $startTime = microtime(true);

    // Query specific field set
    $results = ExampleFlexyModel::whereFieldSet('set1')->get();

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Should complete in less than 1 second
    expect($duration)->toBeLessThan(1.0);
    expect($results)->toHaveCount(100); // 1000 / 10 = 100 per set
});
