<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(CreatesSchemas::class);

beforeEach(function () {

    Schema::dropIfExists('ff_example_flexy_models'); Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable()->index();
        $table->timestamps();
    });
});

it('queries models by schema code', function () {
    // Create two schemas
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'schema1',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'schema2',
        fields: [
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different schemas
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('schema1');
    $model1->flexy->field1 = 'value1';
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('schema2');
    $model2->flexy->field2 = 42;
    $model2->save();

    // Query by schema code
    $schema1Models = ExampleFlexyModel::whereSchema('schema1')->get();
    $schema2Models = ExampleFlexyModel::whereSchema('schema2')->get();

    // Check results
    expect($schema1Models)->toHaveCount(1);
    expect($schema1Models->first()->id)->toBe($model1->id);
    expect($schema2Models)->toHaveCount(1);
    expect($schema2Models->first()->id)->toBe($model2->id);
});

it('queries models by multiple schema codes', function () {
    // Create two schemas
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'schema1',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'schema2',
        fields: [
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different schemas
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('schema1');
    $model1->flexy->field1 = 'value1';
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('schema2');
    $model2->flexy->field2 = 42;
    $model2->save();

    // Query by multiple schema codes
    $models = ExampleFlexyModel::whereInSchema(['schema1', 'schema2'])->get();

    // Check results
    expect($models)->toHaveCount(2);
    $modelIds = $models->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
    expect($modelIds)->toContain($model2->id);
});

it('queries models without schema assignment', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ],
        isDefault: false
    );

    // Create models with and without schema assignment
    $modelWithSchema = ExampleFlexyModel::create(['name' => 'With Schema']);
    $modelWithSchema->assignToSchema('test');
    $modelWithSchema->flexy->field1 = 'value';
    $modelWithSchema->save();

    $modelWithoutSchema = ExampleFlexyModel::create(['name' => 'Without Schema']);
    // Don't assign to any schema

    // Query models with and without schema assignment
    $modelsWithSchema = ExampleFlexyModel::whereHasSchema()->get();
    $modelsWithoutSchema = ExampleFlexyModel::whereDoesntHaveSchema()->get();

    // Check results
    expect($modelsWithSchema)->toHaveCount(1);
    expect($modelsWithoutSchema)->toHaveCount(1);
    expect($modelsWithSchema->first()->id)->toBe($modelWithSchema->id);
    expect($modelsWithoutSchema->first()->id)->toBe($modelWithoutSchema->id);
});

it('queries models by default schema', function () {
    // Create a default schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'default',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ],
        isDefault: true
    );

    // Create models
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('default');
    $model1->flexy->field1 = 'value1';
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('default');
    $model2->flexy->field1 = 'value2';
    $model2->save();

    // Query by default schema
    $defaultModels = ExampleFlexyModel::whereDefaultSchema()->get();

    // Check results
    expect($defaultModels)->toHaveCount(2);
    $modelIds = $defaultModels->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
    expect($modelIds)->toContain($model2->id);
});

it('queries models by flexy field values', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->flexy->field2 = 100;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value2';
    $model2->flexy->field2 = 200;
    $model2->save();

    // Query by field values
    $results1 = ExampleFlexyModel::where('flexy_field1', 'value1')->get();
    $results2 = ExampleFlexyModel::where('flexy_field2', 100)->get();
    $results3 = ExampleFlexyModel::where('flexy_field1', 'value1')
        ->where('flexy_field2', 100)->get();

    // Check results
    expect($results1)->toHaveCount(1);
    expect($results1->first()->id)->toBe($model1->id);
    expect($results2)->toHaveCount(1);
    expect($results2->first()->id)->toBe($model1->id);
    expect($results3)->toHaveCount(1);
    expect($results3->first()->id)->toBe($model1->id);
});

it('queries models by multiple field values', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->flexy->field2 = 100;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value1';
    $model2->flexy->field2 = 200;
    $model2->save();

    // Query by multiple field values
    $results = ExampleFlexyModel::where('flexy_field1', 'value1')
        ->where('flexy_field2', 200)->get();

    // Check results
    expect($results)->toHaveCount(1);
    $modelIds = $results->pluck('id')->toArray();
    expect($modelIds)->toContain($model2->id);
});

it('queries models by field value ranges', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 100;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 200;
    $model2->save();

    $model3 = ExampleFlexyModel::create(['name' => 'Test 3']);
    $model3->assignToSchema('test');
    $model3->flexy->field1 = 300;
    $model3->save();

    // Query by field value ranges
    $results = ExampleFlexyModel::where('flexy_field1', '>=', 100)
        ->where('flexy_field1', '<=', 200)
        ->get();

    // Check results
    expect($results)->toHaveCount(2);
    $modelIds = $results->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
    expect($modelIds)->toContain($model2->id);
});

it('queries models by field value like conditions', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'test value';
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'different value';
    $model2->save();

    // Query by field value like conditions
    $results = ExampleFlexyModel::where('flexy_field1', 'like', 'test%')->get();

    // Check results
    expect($results)->toHaveCount(1);
    $modelIds = $results->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
});

it('queries models by field value in array', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value2';
    $model2->save();

    // Query by field value in array
    $results = ExampleFlexyModel::whereIn('flexy_field1', ['value1', 'value2'])->get();

    // Check results
    expect($results)->toHaveCount(2);
    $modelIds = $results->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
    expect($modelIds)->toContain($model2->id);
});

it('queries models by field value null condition', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->flexy->field2 = 100;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value2';
    $model2->save();

    // Query by field value null condition
    $results = ExampleFlexyModel::whereNull('flexy_field1')->get();

    // Check results
    expect($results)->toHaveCount(0);
});

it('queries models by field value not null condition', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->flexy->field2 = 100;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value2';
    $model2->save();

    // Query by field value not null condition
    $results = ExampleFlexyModel::whereNotNull('flexy_field1')->get();

    // Check results
    expect($results)->toHaveCount(2);
    $modelIds = $results->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
    expect($modelIds)->toContain($model2->id);
});

it('queries models by field value between condition', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 100;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 200;
    $model2->save();

    // Query by field value between condition
    $results = ExampleFlexyModel::whereBetween('flexy_field1', [100, 200])->get();

    // Check results
    expect($results)->toHaveCount(2);
    $modelIds = $results->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
    expect($modelIds)->toContain($model2->id);
});

it('queries models by field value date conditions', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::DATE],
        ]
    );

    // Create models with different date values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = now()->subDays(7)->format('Y-m-d');
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = now()->format('Y-m-d');
    $model2->save();

    // Query by field value date conditions
    $results = ExampleFlexyModel::whereDate('flexy_field1', '>=', now()->subDays(7)->format('Y-m-d'))
        ->whereDate('flexy_field1', '<=', now()->format('Y-m-d'))
        ->get();

    // Check results
    expect($results)->toHaveCount(2);
    $modelIds = $results->pluck('id')->toArray();
    expect($modelIds)->toContain($model1->id);
    expect($modelIds)->toContain($model2->id);
});

it('handles complex queries with multiple conditions', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::INTEGER],
            'field3' => ['type' => FlexyFieldType::BOOLEAN],
        ]
    );

    // Create models with different field values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->field1 = 'value1';
    $model1->flexy->field2 = 100;
    $model1->flexy->field3 = true;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->field1 = 'value2';
    $model2->flexy->field2 = 200;
    $model2->flexy->field3 = false;
    $model2->save();

    // Complex query with multiple conditions
    $results = ExampleFlexyModel::where('flexy_field1', 'value1')
        ->where('flexy_field2', '>', 150)
        ->where('flexy_field3', true)
        ->get();

    // Check results
    expect($results)->toHaveCount(0);
});

it('handles dynamic where methods', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');
    $model->flexy->field1 = 'test value';
    $model->save();

    // Query using dynamic where method
    $results = ExampleFlexyModel::whereFlexyField1('test value')->get();

    // Check results
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($model->id);
});
