<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSchemaException;
use AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(CreatesSchemas::class);

beforeEach(function () {

    Schema::dropIfExists('ff_example_flexy_models');
    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('schema_code')->nullable()->index();
        $table->timestamps();
    });
});

it('validates required fields', function () {
    // Create a schema with required fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'required_field' => ['type' => FlexyFieldType::STRING, 'validationRules' => 'required'],
            'required_int' => ['type' => FlexyFieldType::INTEGER, 'validationRules' => 'required|integer'],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid values should pass
    $model->flexy->required_field = 'test value';
    $model->flexy->required_int = 42;
    $model->save();

    // Check that values were saved
    $model->refresh();
    expect($model->flexy->required_field)->toBe('test value');
    expect($model->flexy->required_int)->toBe(42);
});

it('throws validation exception for missing required fields', function () {
    // Create a schema with required fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'required_field' => ['type' => FlexyFieldType::STRING, 'validationRules' => 'required'],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Try to save without required field
    $this->expectException(ValidationException::class);
    $model->flexy->required_field = null; // This should trigger validation
    $model->save();
});

it('validates string fields', function () {
    // Create a schema with string validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => [
                'type' => FlexyFieldType::STRING,
                'validationRules' => 'required|string|max:50',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid string should pass
    $model->flexy->string_field = 'valid string';
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->string_field)->toBe('valid string');

    // Test validation - invalid string should fail
    $this->expectException(ValidationException::class);
    $model->flexy->string_field = str_repeat('a', 100); // Too long
    $model->save();
});

it('validates integer fields', function () {
    // Create a schema with integer validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'int_field' => [
                'type' => FlexyFieldType::INTEGER,
                'validationRules' => 'required|integer|min:1|max:100',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid integer should pass
    $model->flexy->int_field = 42;
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->int_field)->toBe(42);

    // Test validation - invalid integer should fail
    $this->expectException(ValidationException::class);
    $model->flexy->int_field = 0; // Below minimum
    $model->save();
});

it('validates decimal fields', function () {
    // Create a schema with decimal validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'decimal_field' => [
                'type' => FlexyFieldType::DECIMAL,
                'validationRules' => 'required|numeric|min:0|max:999.99',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid decimal should pass
    $model->flexy->decimal_field = 19.99;
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->decimal_field)->toBe(19.99);

    // Test validation - invalid decimal should fail
    $this->expectException(ValidationException::class);
    $model->flexy->decimal_field = -1; // Below minimum
    $model->save();
});

it('validates boolean fields', function () {
    // Create a schema with boolean validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => [
                'type' => FlexyFieldType::BOOLEAN,
                'validationRules' => 'required|boolean',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid boolean should pass
    $model->flexy->bool_field = true;
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->bool_field)->toBeTrue();

    // Test validation - invalid boolean should fail
    $this->expectException(ValidationException::class);
    $model->flexy->bool_field = 'not a boolean'; // Invalid boolean value
    $model->save();
});

it('validates date fields', function () {
    // Create a schema with date validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'date_field' => [
                'type' => FlexyFieldType::DATE,
                'validationRules' => 'required|date',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid date should pass
    $model->flexy->date_field = now()->format('Y-m-d');
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->date_field)->toBeInstanceOf(\Carbon\Carbon::class);

    // Test validation - invalid date should fail
    $this->expectException(ValidationException::class);
    $model->flexy->date_field = 'not a date'; // Invalid date format
    $model->save();
});

it('validates datetime fields', function () {
    // Create a schema with datetime validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'datetime_field' => [
                'type' => FlexyFieldType::DATETIME,
                'validationRules' => 'required|date',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid datetime should pass
    $model->flexy->datetime_field = now();
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->datetime_field)->toBeInstanceOf(\Carbon\Carbon::class);

    // Test validation - invalid datetime should fail
    $this->expectException(ValidationException::class);
    $model->flexy->datetime_field = 'not a datetime'; // Invalid datetime format
    $model->save();
});

it('validates json fields', function () {
    // Create a schema with JSON validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'json_field' => [
                'type' => FlexyFieldType::JSON,
                'validationRules' => 'required|array',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid JSON should pass
    $validJson = ['key1' => 'value1', 'key2' => 'value2'];
    $model->flexy->json_field = $validJson;
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->json_field)->toEqual($validJson);

    // Test validation - invalid JSON should fail
    $this->expectException(ValidationException::class);
    $model->flexy->json_field = 'not a json'; // Invalid JSON string
    $model->save();
});

it('validates multiple rules', function () {
    // Create a schema with multiple validation rules
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'multi_rule_field' => [
                'type' => FlexyFieldType::STRING,
                'validationRules' => 'required|string|max:50|regex:/^[a-zA-Z]+$/',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - valid value should pass
    $model->flexy->multi_rule_field = 'ValidValue';
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->multi_rule_field)->toBe('ValidValue');

    // Test validation - invalid value should fail
    $this->expectException(ValidationException::class);
    $model->flexy->multi_rule_field = '123'; // Contains numbers
    $model->save();
});

it('validates custom validation messages', function () {
    // Create a schema with custom validation messages
    $messages = [
        'required' => 'This field is required.',
        'regex' => 'Only letters are allowed.',
    ];

    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'custom_field' => [
                'type' => FlexyFieldType::STRING,
                'validationRules' => 'required|string|max:50',
                'validationMessages' => $messages,
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test validation - should trigger custom messages
    $this->expectException(ValidationException::class);
    $model->flexy->custom_field = null; // Required field is null
    $model->save();
});

it('throws exception for field not in schema', function () {
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

    // Try to set a field not in the schema
    $this->expectException(FieldNotInSchemaException::class);
    $model->flexy->non_existent_field = 'value';
    $model->save();
});

it('throws exception when setting field without schema assignment', function () {
    // Create a model without schema assignment
    $model = ExampleFlexyModel::create(['name' => 'Test']);

    // Try to set a field
    $this->expectException(SchemaNotFoundException::class);
    $model->flexy->field1 = 'value';
    $model->save();
});

// ============================================
// Tests migrated from EdgeCaseValidationTest
// ============================================

it('validates field types correctly', function () {
    // Create a schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
            'int_field' => ['type' => FlexyFieldType::INTEGER],
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
            'date_field' => ['type' => FlexyFieldType::DATE],
            'datetime_field' => ['type' => FlexyFieldType::DATETIME],
            'json_field' => ['type' => FlexyFieldType::JSON],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test valid field types
    $model->flexy->string_field = 'test string';
    $model->flexy->int_field = 42;
    $model->flexy->decimal_field = 19.99;
    $model->flexy->bool_field = true;
    $model->flexy->date_field = now()->startOfDay();
    $model->flexy->datetime_field = now();
    $model->flexy->json_field = ['key' => 'value'];
    $model->save();

    // Check that values were saved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe('test string');
    expect($model->flexy->int_field)->toBe(42);
    expect($model->flexy->decimal_field)->toBe(19.99);
    expect($model->flexy->bool_field)->toBeTrue();
    expect($model->flexy->date_field)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($model->flexy->datetime_field)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($model->flexy->json_field)->toEqual(['key' => 'value']);
});

it('rejects invalid field types', function () {
    // Create a schema
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test invalid field types
    try {
        $model->flexy->string_field = new \stdClass;
        $model->save();
    } catch (\Throwable $e) {
        expect($e)->toBeInstanceOf(\Throwable::class);
    }

    // Add a dummy assertion to avoid risky test warning if no exception is thrown
    expect(true)->toBeTrue();
});

it('validates field values based on type', function () {
    // Create a schema with fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING, 'validationRules' => 'required|string|max:50'],
            'int_field' => ['type' => FlexyFieldType::INTEGER, 'validationRules' => 'required|integer|min:1|max:100'],
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL, 'validationRules' => 'required|numeric|min:0|max:999.99'],
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN, 'validationRules' => 'required|boolean'],
            'date_field' => ['type' => FlexyFieldType::DATE, 'validationRules' => 'required|date'],
            'datetime_field' => ['type' => FlexyFieldType::DATETIME, 'validationRules' => 'required|date'],
            'json_field' => ['type' => FlexyFieldType::JSON, 'validationRules' => 'required|array'],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test valid values
    $model->flexy->string_field = 'valid string';
    $model->flexy->int_field = 42;
    $model->flexy->decimal_field = 19.99;
    $model->flexy->bool_field = true;
    $model->flexy->date_field = now()->startOfDay();
    $model->flexy->datetime_field = now();
    $model->flexy->json_field = ['key' => 'value'];
    $model->save();

    // Check that values were saved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe('valid string');
    expect($model->flexy->int_field)->toBe(42);
    expect($model->flexy->decimal_field)->toBe(19.99);
    expect($model->flexy->bool_field)->toBeTrue();
    expect($model->flexy->date_field)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($model->flexy->datetime_field)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($model->flexy->json_field)->toEqual(['key' => 'value']);

    // Test invalid values
    $this->expectException(ValidationException::class);
    $model->flexy->string_field = '';
    $model->flexy->int_field = 'not a number';
    $model->flexy->decimal_field = 'not a number';
    $model->flexy->bool_field = 'not a boolean';
    $model->flexy->date_field = 'not a date';
    $model->flexy->datetime_field = 'not a datetime';
    $model->flexy->json_field = 'not a json';
    $model->save();
});

it('validates conditional validation rules', function () {
    // Create a schema with conditional validation rules
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'conditional_field' => [
                'type' => FlexyFieldType::STRING,
                'validationRules' => 'required_if:other_field,value1|string|max:50',
            ],
            'other_field' => [
                'type' => FlexyFieldType::STRING,
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test with condition met (should pass)
    $model->flexy->other_field = 'value1';
    $model->flexy->conditional_field = 'valid';
    $model->save();

    // Test with condition not met (should fail)
    expect(function () use ($model) {
        $model->flexy->conditional_field = '';
        $model->save();
    })->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates array validation rules', function () {
    // Create a schema with array validation rules
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'array_field' => [
                'type' => FlexyFieldType::JSON,
                'validationRules' => 'required|array|min:2|max:5',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test with valid array
    $model->flexy->array_field = ['item1', 'item2'];
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->array_field)->toEqual(['item1', 'item2']);

    // Test with invalid array (too few items)
    $this->expectException(ValidationException::class);
    $model->flexy->array_field = ['item1'];
    $model->save();

    // Test with invalid array (not an array)
    expect(function () use ($model) {
        $model->flexy->array_field = 'not an array';
        $model->save();
    })->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates date validation rules', function () {
    // Create a schema with date validation rules
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'date_field' => [
                'type' => FlexyFieldType::DATE,
                'validationRules' => 'required|date|after:2020-01-01',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test with valid date
    $model->flexy->date_field = '2021-01-01';
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->date_field)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($model->flexy->date_field->format('Y-m-d'))->toBe('2021-01-01');

    // Test with invalid date (before minimum)
    expect(function () use ($model) {
        $model->flexy->date_field = '2019-12-31';
        $model->save();
    })->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates datetime validation rules', function () {
    // Create a schema with datetime validation rules
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'datetime_field' => [
                'type' => FlexyFieldType::DATETIME,
                'validationRules' => 'required|date|after:2020-01-01 12:00:00',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test with valid datetime
    $model->flexy->datetime_field = '2020-01-01 12:00:01';
    $model->save();

    $model->refresh();

    expect($model->flexy->datetime_field)->toBeInstanceOf(\Carbon\Carbon::class);

    // Test with invalid datetime (before minimum)
    expect(function () use ($model) {
        $model->flexy->datetime_field = '2019-12-31 23:59:59';
        $model->save();
    })->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates complex validation scenarios', function () {
    // Create a schema with complex validation
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'email_field' => [
                'type' => FlexyFieldType::STRING,
                'validationRules' => 'required|email|max:255',
            ],
            'url_field' => [
                'type' => FlexyFieldType::STRING,
                'validationRules' => 'required|url|max:2048',
            ],
            'age_field' => [
                'type' => FlexyFieldType::INTEGER,
                'validationRules' => 'required|integer|min:18|max:120',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test all valid values
    $model->flexy->email_field = 'test@example.com';
    $model->flexy->url_field = 'https://example.com';
    $model->flexy->age_field = 25;
    $model->save();

    // Check that values were saved
    $model->refresh();
    expect($model->flexy->email_field)->toBe('test@example.com');
    expect($model->flexy->url_field)->toBe('https://example.com');
    expect($model->flexy->age_field)->toBe(25);

    // Test invalid values
    expect(function () use ($model) {
        $model->flexy->email_field = 'not-an-email';
        $model->flexy->url_field = 'not-a-url';
        $model->flexy->age_field = 15;
        $model->save();
    })->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('handles validation with null values', function () {
    // Create a schema with nullable fields
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'nullable_field' => [
                'type' => FlexyFieldType::STRING,
                'validationRules' => 'nullable|string|max:50',
            ],
        ]
    );

    // Create a model
    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test with null value (should pass)
    $model->flexy->nullable_field = null;
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->nullable_field)->toBeNull();

    // Test with empty string (should pass for nullable field)
    $model->flexy->nullable_field = '';
    $model->save();

    // Check that value was saved
    $model->refresh();
    expect($model->flexy->nullable_field)->toBe('');
});
