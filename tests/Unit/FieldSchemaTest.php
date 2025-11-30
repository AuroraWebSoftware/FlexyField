<?php

use AuroraWebSoftware\FlexyField\Models\FieldSchema;
use AuroraWebSoftware\FlexyField\Models\SchemaField;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {

    Schema::dropIfExists('ff_example_flexy_models');
    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable()->index();
        $table->timestamps();

        // REMOVED FOR PGSQL:         $table->foreign('schema_code')
        // REMOVED FOR PGSQL:             ->references('schema_code')
        // REMOVED FOR PGSQL:             ->on('ff_schemas')
        // REMOVED FOR PGSQL:             ->onDelete('set null')
        // REMOVED FOR PGSQL:             ->onUpdate('cascade');
    });
});

it('can create a schema', function () {
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'shoes',
        'label' => 'Shoes',
        'description' => 'Fields for shoe products',
        'is_default' => false,
    ]);

    expect($schema)->toBeInstanceOf(FieldSchema::class)
        ->and($schema->model_type)->toBe(ExampleFlexyModel::class)
        ->and($schema->schema_code)->toBe('shoes')
        ->and($schema->label)->toBe('Shoes')
        ->and($schema->is_default)->toBeFalse();
});

it('enforces only one default schema per model type', function () {
    // Create first default
    $schema1 = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default1',
        'label' => 'Default 1',
        'is_default' => true,
    ]);

    expect($schema1->is_default)->toBeTrue();

    // Create second default - should make first non-default
    $schema2 = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default2',
        'label' => 'Default 2',
        'is_default' => true,
    ]);

    expect($schema2->fresh()->is_default)->toBeTrue()
        ->and($schema1->fresh()->is_default)->toBeFalse();
});

it('can store metadata as JSON', function () {
    $metadata = ['category' => 'apparel', 'priority' => 1];

    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'test',
        'label' => 'Test',
        'metadata' => $metadata,
    ]);

    expect($schema->fresh()->metadata)->toBe($metadata);
});

it('has fields relationship', function () {
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'size',
        'type' => 'string',
        'sort' => 1,
    ]);

    SchemaField::create([
        'schema_code' => 'shoes',
        'name' => 'color',
        'type' => 'string',
        'sort' => 2,
    ]);

    expect($schema->fields)->toHaveCount(2)
        ->and($schema->fields->pluck('name')->toArray())->toBe(['size', 'color']);
});

it('can scope by model type', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'set1',
        'label' => 'Set 1',
    ]);

    FieldSchema::create([
        'model_type' => 'App\\Models\\Product',
        'schema_code' => 'set2',
        'label' => 'Set 2',
    ]);

    $schemas = FieldSchema::forModel(ExampleFlexyModel::class)->get();

    expect($schemas)->toHaveCount(1)
        ->and($schemas->first()->schema_code)->toBe('set1');
});

it('can get default schema for model type', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'non_default',
        'label' => 'Non Default',
        'is_default' => false,
    ]);

    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'default',
        'label' => 'Default',
        'is_default' => true,
    ]);

    $defaultSchema = FieldSchema::default(ExampleFlexyModel::class)->first();

    expect($defaultSchema->schema_code)->toBe('default')
        ->and($defaultSchema->is_default)->toBeTrue();
});

it('can check usage count', function () {
    $schema = FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'shoes',
        'label' => 'Shoes',
    ]);

    // Create 3 models assigned to this schema
    for ($i = 1; $i <= 3; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Model {$i}"]);
        $model->schema_code = 'shoes';
        $model->save();
    }

    expect($schema->getUsageCount(ExampleFlexyModel::class))->toBe(3)
        ->and($schema->isInUse(ExampleFlexyModel::class))->toBeTrue();
});

it('enforces unique constraint on model_type and schema_code', function () {
    FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'duplicate',
        'label' => 'First',
    ]);

    expect(fn () => FieldSchema::create([
        'model_type' => ExampleFlexyModel::class,
        'schema_code' => 'duplicate',
        'label' => 'Second',
    ]))->toThrow(\Exception::class);
});
