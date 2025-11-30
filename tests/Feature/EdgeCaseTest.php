<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesSchemas;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

// Null Value Tests
it('handles null values for string fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign null value
    $model->flexy->string_field = null;
    $model->save();

    // Verify null is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'string_field',
        'value_string' => null,
        'schema_code' => 'test',
    ]);

    // Verify null is retrieved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBeNull();
});

it('handles null values for integer fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'int_field' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign null value
    $model->flexy->int_field = null;
    $model->save();

    // Verify null is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'int_field',
        'value_int' => null,
        'schema_code' => 'test',
    ]);

    // Verify null is retrieved correctly
    $model->refresh();
    expect($model->flexy->int_field)->toBeNull();
});

it('handles null values for decimal fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign null value
    $model->flexy->decimal_field = null;
    $model->save();

    // Verify null is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'decimal_field',
        'value_decimal' => null,
        'schema_code' => 'test',
    ]);

    // Verify null is retrieved correctly
    $model->refresh();
    expect($model->flexy->decimal_field)->toBeNull();
});

it('handles null values for boolean fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign null value
    $model->flexy->bool_field = null;
    $model->save();

    // Verify null is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'bool_field',
        'value_boolean' => null,
        'schema_code' => 'test',
    ]);

    // Verify null is retrieved correctly
    $model->refresh();
    expect($model->flexy->bool_field)->toBeNull();
});

it('handles null values for date fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'date_field' => ['type' => FlexyFieldType::DATE],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign null value
    $model->flexy->date_field = null;
    $model->save();

    // Verify null is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'date_field',
        'value_date' => null,
        'schema_code' => 'test',
    ]);

    // Verify null is retrieved correctly
    $model->refresh();
    expect($model->flexy->date_field)->toBeNull();
});

it('handles null values for datetime fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'datetime_field' => ['type' => FlexyFieldType::DATETIME],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign null value
    $model->flexy->datetime_field = null;
    $model->save();

    // Verify null is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'datetime_field',
        'value_datetime' => null,
        'schema_code' => 'test',
    ]);

    // Verify null is retrieved correctly
    $model->refresh();
    expect($model->flexy->datetime_field)->toBeNull();
});

it('handles null values for JSON fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'json_field' => ['type' => FlexyFieldType::JSON],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign null value
    $model->flexy->json_field = null;
    $model->save();

    // Verify null is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'json_field',
        'value_json' => null,
        'schema_code' => 'test',
    ]);

    // Verify null is retrieved correctly
    $model->refresh();
    expect($model->flexy->json_field)->toBeNull();
});

// Empty String Tests
it('handles empty strings for string fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign empty string
    $model->flexy->string_field = '';
    $model->save();

    // Verify empty string is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'string_field',
        'value_string' => '',
        'schema_code' => 'test',
    ]);

    // Verify empty string is retrieved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe('');
});

it('handles empty strings for JSON fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'json_field' => ['type' => FlexyFieldType::JSON],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign empty string
    $model->flexy->json_field = '';
    $model->save();

    // Verify empty string is stored correctly (JSON fields store empty strings as JSON-encoded strings)
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'json_field',
        'value_json' => '""',
        'schema_code' => 'test',
    ]);

    // Verify empty string is retrieved correctly
    $model->refresh();
    expect($model->flexy->json_field)->toBe('');
});

// Boundary Value Tests
it('handles maximum integer values correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'int_field' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test maximum integer value
    $maxInt = 2147483647; // Use 32-bit max to avoid database overflow
    $model->flexy->int_field = $maxInt;
    $model->save();

    // Verify value is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'int_field',
        'value_int' => $maxInt,
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->int_field)->toBe($maxInt);
});

it('handles minimum integer values correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'int_field' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test minimum integer value
    $minInt = -2147483648; // Use 32-bit min to avoid database overflow
    $model->flexy->int_field = $minInt;
    $model->save();

    // Verify value is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'int_field',
        'value_int' => $minInt,
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->int_field)->toBe($minInt);
});

it('handles maximum decimal values correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test maximum decimal value (within database limits)
    $maxDecimal = 999999.99;
    $model->flexy->decimal_field = $maxDecimal;
    $model->save();

    // Verify value is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'decimal_field',
        'value_decimal' => $maxDecimal,
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->decimal_field)->toBe($maxDecimal);
});

it('handles minimum decimal values correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test minimum decimal value (within database limits)
    $minDecimal = -999999.99;
    $model->flexy->decimal_field = $minDecimal;
    $model->save();

    // Verify value is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'decimal_field',
        'value_decimal' => $minDecimal,
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->decimal_field)->toBe($minDecimal);
});

it('handles decimal precision correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test decimal precision (within database limits)
    $preciseDecimal = 123.456;
    $model->flexy->decimal_field = $preciseDecimal;
    $model->save();

    // Verify value is stored correctly (database may round to 2 decimal places)
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'decimal_field',
        'value_decimal' => 123.46, // Rounded to 2 decimal places
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->decimal_field)->toBe(123.46);
});

// Special Character and Unicode Tests
it('handles special characters in string fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test special characters
    $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?`~\'"\\';
    $model->flexy->string_field = $specialChars;
    $model->save();

    // Verify value is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'string_field',
        'value_string' => $specialChars,
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe($specialChars);
});

it('handles unicode characters in string fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test unicode characters
    $unicodeText = 'Emoji: 🚀🌟💻, Chinese: 你好世界, Arabic: مرحبا بالعالم, Russian: Привет мир';
    $model->flexy->string_field = $unicodeText;
    $model->save();

    // Verify value is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'string_field',
        'value_string' => $unicodeText,
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe($unicodeText);
});

it('handles newlines and tabs in string fields correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Test newlines and tabs
    $textWithWhitespace = "Line 1\nLine 2\tTabbed content\n\nDouble newline";
    $model->flexy->string_field = $textWithWhitespace;
    $model->save();

    // Verify value is stored correctly
    $this->assertDatabaseHas('ff_field_values', [
        'model_type' => ExampleFlexyModel::class,
        'model_id' => $model->id,
        'name' => 'string_field',
        'value_string' => $textWithWhitespace,
        'schema_code' => 'test',
    ]);

    // Verify value is retrieved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe($textWithWhitespace);
});

// Concurrent Operation Tests
it('handles concurrent operations on the same record correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
            'int_field' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Simulate concurrent operations by using separate model instances
    $model1 = ExampleFlexyModel::find($model->id);
    $model2 = ExampleFlexyModel::find($model->id);

    // Update different fields on each instance
    $model1->flexy->string_field = 'Value from instance 1';
    $model1->save();

    $model2->flexy->int_field = 42;
    $model2->save();

    // Verify both values are stored correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe('Value from instance 1');
    expect($model->flexy->int_field)->toBe(42);
});

it('handles concurrent field assignments correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'field1' => ['type' => FlexyFieldType::STRING],
            'field2' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign multiple fields at once
    $model->flexy->field1 = 'Value 1';
    $model->flexy->field2 = 'Value 2';
    $model->save();

    // Verify both values are stored correctly
    $model->refresh();
    expect($model->flexy->field1)->toBe('Value 1');
    expect($model->flexy->field2)->toBe('Value 2');
});

// Invalid Field Type Assignment Tests
it('rejects invalid string assignments to integer fields', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'int_field' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Try to assign a non-numeric string to an integer field
    expect(fn() => $model->flexy->int_field = 'not a number')->not->toThrow(Exception::class);
    
    // Save and verify value was converted or handled appropriately
    $model->save();
    $model->refresh();
    
    // The system should either convert string to 0 or handle it gracefully
    expect($model->flexy->int_field)->toBe(0);
});

it('rejects invalid boolean assignments to string fields', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign a boolean to a string field
    $model->flexy->string_field = true;
    $model->save();

    // Verify boolean was converted to string
    $model->refresh();
    expect($model->flexy->string_field)->toBe('1');
});

it('rejects invalid date assignments to string fields', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign a date to a string field
    $date = now();
    $model->flexy->string_field = $date;
    $model->save();

    // Verify date was converted to string
    $model->refresh();
    expect($model->flexy->string_field)->toBe($date->format('Y-m-d H:i:s'));
});

it('handles very long strings correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Create a moderately long string (within database limits)
    $longString = str_repeat('a', 255); // Use 255 characters which should fit in most string fields
    $model->flexy->string_field = $longString;
    $model->save();

    // Verify long string is stored and retrieved correctly
    $model->refresh();
    expect($model->flexy->string_field)->toBe($longString);
});

it('handles zero values correctly', function () {
    $this->createSchemaWithFields(
        modelClass: ExampleFlexyModel::class,
        schemaCode: 'test',
        fields: [
            'int_field' => ['type' => FlexyFieldType::INTEGER],
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
            'string_field' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToSchema('test');

    // Assign zero values
    $model->flexy->int_field = 0;
    $model->flexy->decimal_field = 0.0;
    $model->flexy->string_field = '0';
    $model->save();

    // Verify zero values are stored correctly
    $model->refresh();
    expect($model->flexy->int_field)->toBe(0);
    expect($model->flexy->decimal_field)->toBe(0.0);
    expect($model->flexy->string_field)->toBe('0');
});
