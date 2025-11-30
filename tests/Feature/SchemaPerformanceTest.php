<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(CreatesSchemas::class)->group('performance');

beforeEach(function () {

    Schema::dropIfExists('ff_example_flexy_models');
    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable()->index();
        $table->timestamps();
    });
});

it('handles large number of fields efficiently', function () {
    // Create a schema with many fields
    $fields = [];
    for ($i = 1; $i <= 100; $i++) {
        $fields["field_{$i}"] = ['type' => FlexyFieldType::STRING];
    }
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: $fields
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Measure time for setting all fields
    $startTime = microtime(true);
    for ($i = 1; $i <= 100; $i++) {
        $model->flexy->{"field_{$i}"} = "value {$i}";
    }
    $model->save();
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // Should complete within reasonable time (less than 1 second for 100 fields)
    expect($totalTime)->toBeLessThan(1.0);
});

it('handles concurrent field creation efficiently', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create multiple models concurrently
    $startTime = microtime(true);
    $models = [];
    for ($i = 0; $i < 50; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Test {$i}"]);
        $model->assignToSchema('test');
        $model->flexy->field1 = "value {$i}";
        $model->save();
        $models[] = $model;
    }
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // Should complete within reasonable time
    expect($totalTime)->toBeLessThan(2.0);
    expect(count($models))->toBe(50);
});

it('measures query performance with many fields', function () {
    // Create a schema with many fields
    $fields = [];
    for ($i = 1; $i <= 50; $i++) {
        $fields["field_{$i}"] = ['type' => FlexyFieldType::STRING];
    }
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: $fields
    );

    // Create models with values
    for ($i = 0; $i < 100; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Test {$i}"]);
        $model->assignToSchema('test');
        for ($j = 1; $j <= 50; $j++) {
            $model->flexy->{"field_{$j}"} = "value {$i}_{$j}";
        }
        $model->save();
    }

    // Measure query performance
    $startTime = microtime(true);
    $results = ExampleFlexyModel::whereSchema('test')->get();
    $endTime = microtime(true);
    $queryTime = $endTime - $startTime;

    // Query should complete within reasonable time
    expect($queryTime)->toBeLessThan(0.5);
    expect($results)->toHaveCount(100);
});

it('handles memory usage efficiently', function () {
    $initialMemory = memory_get_usage(true);

    // Create a schema with many fields
    $fields = [];
    for ($i = 1; $i <= 100; $i++) {
        $fields["field_{$i}"] = ['type' => FlexyFieldType::STRING];
    }
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: $fields
    );

    // Create models with values
    for ($i = 0; $i < 50; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Test {$i}"]);
        $model->assignToSchema('test');
        for ($j = 1; $j <= 100; $j++) {
            $model->flexy->{"field_{$j}"} = "value {$i}_{$j}";
        }
        $model->save();
    }

    $finalMemory = memory_get_usage(true);
    $memoryIncrease = $finalMemory - $initialMemory;

    // Memory usage should be reasonable (less than 50MB for 5000 field values)
    expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024);
});

it('optimizes database queries', function () {
    // Create a schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Enable query logging
    DB::enableQueryLog();

    // Create models and query
    for ($i = 0; $i < 10; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Test {$i}"]);
        $model->assignToSchema('test');
        $model->flexy->field1 = "value {$i}";
        $model->flexy->field2 = $i;
        $model->save();
    }

    $results = ExampleFlexyModel::whereSchema('test')->get();
    $queryLog = DB::getQueryLog();

    // Should use efficient queries (minimal number of queries)
    $queryCount = count($queryLog);
    expect($queryCount)->toBeLessThan(150); // Allow reasonable query count for test environment
});
