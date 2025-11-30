<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldValue;
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

it('stores boolean field values correctly', function () {
    // Create a schema with boolean field
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test true value
    $model->flexy->bool_field = true;
    $model->save();

    // Check that value was stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'bool_field',
        'value_boolean' => true,
        'schema_code' => 'test',
    ]);

    // Test false value
    $model->flexy->bool_field = false;
    $model->save();

    // Check that value was stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'bool_field',
        'value_boolean' => false,
        'schema_code' => 'test',
    ]);

    // Test null value
    $model->flexy->bool_field = null;
    $model->save();

    // Check that value was stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'bool_field',
        'value_boolean' => null,
        'schema_code' => 'test',
    ]);

    // Refresh model and check retrieval for each value
    $model->refresh();
    expect($model->flexy->bool_field)->toBeNull();
    
    // Set and check true value
    $model->flexy->bool_field = true;
    $model->save();
    $model->refresh();
    expect($model->flexy->bool_field)->toBeTrue();
    
    // Set and check false value
    $model->flexy->bool_field = false;
    $model->save();
    $model->refresh();
    expect($model->flexy->bool_field)->toBeFalse();
});

it('retrieves boolean field values correctly', function () {
    // Create a schema with boolean fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
            'bool_field2' => ['type' => FlexyFieldType::BOOLEAN],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set boolean values
    $model->flexy->bool_field = true;
    $model->flexy->bool_field2 = false;
    $model->save();

    // Create another model
    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->bool_field = true;
    $model2->flexy->bool_field2 = false;
    $model2->save();

    // Refresh models and check retrieval
    $model->refresh();
    $model2->refresh();

    expect($model->flexy->bool_field)->toBeTrue();
    expect($model->flexy->bool_field2)->toBeFalse();

    expect($model->flexy->bool_field)->toBeBool();
    expect($model2->flexy->bool_field2)->toBeBool();
});

it('handles boolean field validation correctly', function () {
    // Create a schema with boolean field
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => [
                'type' => FlexyFieldType::BOOLEAN,
                'rules' => 'required|boolean',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test valid boolean values
    $model->flexy->bool_field = true;
    $model->save();
    
    $model->flexy->bool_field = false;
    $model->save();

    // Check that values were saved correctly
    $model->refresh();
    expect($model->flexy->bool_field)->toBeFalse();

    // Test invalid boolean values - string that's not a boolean
    expect(function () use ($model) {
        $model->flexy->bool_field = 'not a boolean';
        $model->save();
    })->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('handles boolean field filtering correctly', function () {
    // Create a schema with boolean field
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
        ]
    );

    // Create models with different boolean values
    $model1 = ExampleFlexyModel::create(['name' => 'Test 1']);
    $model1->assignToSchema('test');
    $model1->flexy->bool_field = true;
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');
    $model2->flexy->bool_field = false;
    $model2->save();

    // Query models with boolean field = true
    $trueModels = ExampleFlexyModel::where('flexy_bool_field', true)->get();

    // Query models with boolean field = false
    $falseModels = ExampleFlexyModel::where('flexy_bool_field', false)->get();

    // Check results
    expect($trueModels)->toHaveCount(1);
    expect($falseModels)->toHaveCount(1);
    expect($trueModels->first()->flexy->bool_field)->toBeTrue();
    expect($falseModels->first()->flexy->bool_field)->toBeFalse();
});

it('handles boolean field sorting correctly', function () {
    // Create a schema with boolean fields in specific order
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field1' => ['type' => FlexyFieldType::BOOLEAN, 'sort' => 1],
            'bool_field2' => ['type' => FlexyFieldType::BOOLEAN, 'sort' => 2],
            'bool_field3' => ['type' => FlexyFieldType::BOOLEAN, 'sort' => 3],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Get fields for the schema
    $fields = ExampleFlexyModel::getFieldsForSchema('test');

    // Check that fields are returned in correct order
    expect($fields)->toHaveCount(3);
    expect($fields[0]->name)->toBe('bool_field1');
    expect($fields[0]->sort)->toBe(1);
    expect($fields[1]->name)->toBe('bool_field2');
    expect($fields[1]->sort)->toBe(2);
    expect($fields[2]->name)->toBe('bool_field3');
    expect($fields[2]->sort)->toBe(3);
});

it('handles boolean field deletion correctly', function () {
    // Create a schema with boolean field
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set a boolean value
    $model->flexy->bool_field = true;
    $model->save();

    // Verify value exists
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'bool_field',
        'value_boolean' => true,
        'schema_code' => 'test',
    ]);

    // Remove the field
    ExampleFlexyModel::removeFieldFromSchema('test', 'bool_field');

    // Create another model and assign to schema
    $model2 = ExampleFlexyModel::create(['name' => 'Test 2']);
    $model2->assignToSchema('test');

    // Try to set the removed field
    expect(function () use ($model2) {
        $model2->flexy->bool_field = true;
        $model2->save();
    })->toThrow(\AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSchemaException::class);
});

it('handles boolean field updates correctly', function () {
    // Create a schema with boolean field
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set initial boolean value
    $model->flexy->bool_field = true;
    $model->save();

    // Update the boolean value
    $model->flexy->bool_field = false;
    $model->save();

    // Check that value was updated
    $model->refresh();
    expect($model->flexy->bool_field)->toBeFalse();
});

it('handles boolean field null values correctly', function () {
    // Create a schema with nullable boolean field
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => [
                'type' => FlexyFieldType::BOOLEAN,
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Set null value
    $model->flexy->bool_field = null;
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->bool_field)->toBeNull();

    // Set empty string value (should be converted to false for boolean field)
    $model->flexy->bool_field = '';
    $model->save();

    // Check that value was saved and converted to boolean
    $model->refresh();
    expect($model->flexy->bool_field)->toBeFalse();
});
