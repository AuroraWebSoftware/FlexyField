<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldTypeNotAllowedException;
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

// REMOVED FOR PGSQL:         $table->foreign('field_set_code')
// REMOVED FOR PGSQL:             ->references('set_code')
// REMOVED FOR PGSQL:             ->on('ff_field_sets')
// REMOVED FOR PGSQL:             ->onDelete('set null')
// REMOVED FOR PGSQL:             ->onUpdate('cascade');
    });
});

it('throws exception for unsupported resource type', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['data' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);

    // Try to set a resource (unsupported)
    $resource = fopen('php://memory', 'r');
    $model->flexy->data = $resource;

    expect(fn () => $model->save())->toThrow(FlexyFieldTypeNotAllowedException::class);

    fclose($resource);
});

it('throws exception for unsupported closure type', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['callback' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);

    // Try to set a closure (unsupported)
    $model->flexy->callback = fn () => 'test';

    expect(fn () => $model->save())->toThrow(FlexyFieldTypeNotAllowedException::class);
});

it('handles very large integers', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['large_int' => ['type' => FlexyFieldType::INTEGER]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    $largeInt = PHP_INT_MAX;
    $model->flexy->large_int = $largeInt;
    $model->save();

    expect($model->fresh()->flexy->large_int)->toBe($largeInt);
});

it('handles very large negative integers', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['negative_int' => ['type' => FlexyFieldType::INTEGER]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    $negativeInt = PHP_INT_MIN;
    $model->flexy->negative_int = $negativeInt;
    $model->save();

    expect($model->fresh()->flexy->negative_int)->toBe($negativeInt);
});

it('handles decimal precision correctly', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['price' => ['type' => FlexyFieldType::DECIMAL]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    $preciseDecimal = 123.456789012345;
    $model->flexy->price = $preciseDecimal;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::class)
        ->where('model_id', $model->id)
        ->where('field_name', 'price')
        ->first();

    // Check precision is maintained (within database limits)
    expect((float) $value->value_decimal)->toBeGreaterThan(123.45);
});

it('handles JSON with very large structures', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['metadata' => ['type' => FlexyFieldType::JSON]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Create large nested structure
    $largeData = [];
    for ($i = 0; $i < 100; $i++) {
        $largeData["key_{$i}"] = [
            'nested' => [
                'data' => str_repeat('x', 100),
                'array' => range(1, 100),
            ],
        ];
    }

    $model->flexy->metadata = $largeData;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::class)
        ->where('model_id', $model->id)
        ->where('field_name', 'metadata')
        ->first();

    $decoded = json_decode($value->value_json, true);
    expect($decoded)->toBeArray()
        ->and(count($decoded))->toBe(100);
});

it('handles date with timezone', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['published_at' => ['type' => FlexyFieldType::DATETIME]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    $dateTime = new \DateTime('2024-01-15 14:30:00', new \DateTimeZone('America/New_York'));
    $model->flexy->published_at = $dateTime;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::class)
        ->where('model_id', $model->id)
        ->where('field_name', 'published_at')
        ->first();

    expect($value->value_datetime)->not->toBeNull();
});

it('handles null for each type correctly', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
            'int_field' => ['type' => FlexyFieldType::INTEGER],
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
            'boolean_field' => ['type' => FlexyFieldType::BOOLEAN],
            'date_field' => ['type' => FlexyFieldType::DATE],
            'json_field' => ['type' => FlexyFieldType::JSON],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Set all to null
    $model->flexy->string_field = null;
    $model->flexy->int_field = null;
    $model->flexy->decimal_field = null;
    $model->flexy->boolean_field = null;
    $model->flexy->date_field = null;
    $model->flexy->json_field = null;
    $model->save();

    $fresh = $model->fresh();
    expect($fresh->flexy->string_field)->toBeNull()
        ->and($fresh->flexy->int_field)->toBeNull()
        ->and($fresh->flexy->decimal_field)->toBeNull()
        ->and($fresh->flexy->boolean_field)->toBeNull()
        ->and($fresh->flexy->date_field)->toBeNull()
        ->and($fresh->flexy->json_field)->toBeNull();
});
