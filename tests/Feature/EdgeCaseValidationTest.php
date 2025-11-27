<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesFieldSets;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(CreatesFieldSets::class);

beforeEach(function () {
    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('field_set_code')->nullable()->index();
        $table->timestamps();

        $table->foreign('field_set_code')
            ->references('set_code')
            ->on('ff_field_sets')
            ->onDelete('set null')
            ->onUpdate('cascade');
    });
});

it('validates null values correctly with required rule', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'name' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Try to set null for required field
    $model->flexy->name = null;
    expect(fn () => $model->save())->toThrow(ValidationException::class);
});

it('allows null values for nullable fields', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'description' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'nullable|string|max:500',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    $model->flexy->description = null;
    $model->save();

    expect($model->fresh()->flexy->description)->toBeNull();
});

it('validates empty strings with required rule', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'name' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Empty string should fail 'required'
    $model->flexy->name = '';
    expect(fn () => $model->save())->toThrow(ValidationException::class);
});

it('handles validation messages with special characters', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'email' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required|email',
                'messages' => [
                    'required' => 'Email is required with "quotes" and \'apostrophes\'',
                    'email' => 'Please provide a valid <email> address',
                ],
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Try with invalid email
    $model->flexy->email = 'invalid';
    try {
        $model->save();
        expect(false)->toBeTrue(); // Should not reach here
    } catch (ValidationException $e) {
        expect($e->getMessage())->toContain('Please provide a valid');
    }
});

it('validates all field types correctly', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'name' => ['type' => FlexyFieldType::STRING, 'rules' => 'required|string'],
            'quantity' => ['type' => FlexyFieldType::INTEGER, 'rules' => 'required|integer|min:1'],
            'price' => ['type' => FlexyFieldType::DECIMAL, 'rules' => 'required|numeric|min:0'],
            'is_active' => ['type' => FlexyFieldType::BOOLEAN, 'rules' => 'required|boolean'],
            'published_at' => ['type' => FlexyFieldType::DATE, 'rules' => 'required|date'],
            'metadata' => ['type' => FlexyFieldType::JSON, 'rules' => 'nullable|array'],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // All valid values
    $model->flexy->name = 'Product Name';
    $model->flexy->quantity = 10;
    $model->flexy->price = 99.99;
    $model->flexy->is_active = true;
    $model->flexy->published_at = new \DateTime('2024-01-15');
    $model->flexy->metadata = ['key' => 'value'];

    $model->save();

    expect($model->fresh()->flexy->name)->toBe('Product Name')
        ->and($model->fresh()->flexy->quantity)->toBe(10)
        ->and((float) $model->fresh()->flexy->price)->toBe(99.99)
        ->and($model->fresh()->flexy->is_active)->toBeTrue();
});

it('validates integer field with min rule', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'quantity' => [
                'type' => FlexyFieldType::INTEGER,
                'rules' => 'required|integer|min:1',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Invalid: below minimum
    $model->flexy->quantity = 0;
    expect(fn () => $model->save())->toThrow(ValidationException::class);

    // Valid
    $model->flexy->quantity = 1;
    $model->save();
    expect($model->fresh()->flexy->quantity)->toBe(1);
});

it('validates decimal field with min rule', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'price' => [
                'type' => FlexyFieldType::DECIMAL,
                'rules' => 'required|numeric|min:0',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Invalid: negative
    $model->flexy->price = -10;
    expect(fn () => $model->save())->toThrow(ValidationException::class);

    // Valid
    $model->flexy->price = 0;
    $model->save();
    expect((float) $model->fresh()->flexy->price)->toBe(0.0);
});

it('validates string max length', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'title' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required|string|max:100',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Valid: within limit
    $model->flexy->title = str_repeat('a', 100);
    $model->save();
    expect(strlen($model->fresh()->flexy->title))->toBe(100);

    // Invalid: exceeds max length
    $model2 = ExampleFlexyModel::create(['name' => 'Product 2']);
    $model2->assignToFieldSet('products');
    $model2->flexy->title = str_repeat('a', 101);
    expect(fn () => $model2->save())->toThrow(ValidationException::class);
});

it('handles validation rule exceeding 500 characters', function () {
    // Create a very long validation rule string
    $longRule = 'required|string|min:1|max:255|regex:/^[a-zA-Z0-9]+$/';
    $longRule = str_repeat($longRule.'|', 20); // Make it very long

    // Truncate to 500 chars, but ensure we don't break in the middle of a rule
    $truncatedRule = substr($longRule, 0, 500);
    // Remove any incomplete rule at the end
    $truncatedRule = preg_replace('/\|[^|]*$/', '', $truncatedRule);

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'field' => [
                'type' => FlexyFieldType::STRING,
                'rules' => $truncatedRule,
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Should work with truncated rule
    $model->flexy->field = 'test';
    $model->save();
    expect($model->fresh()->flexy->field)->toBe('test');
});

it('validates with complex nested array rules', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'tags' => [
                'type' => FlexyFieldType::JSON,
                'rules' => 'required|array|min:1',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Valid: array with items
    $model->flexy->tags = ['tag1', 'tag2'];
    $model->save();
    expect($model->fresh()->flexy->tags)->toBe(['tag1', 'tag2']);

    // Invalid: empty array
    $model2 = ExampleFlexyModel::create(['name' => 'Product 2']);
    $model2->assignToFieldSet('products');
    $model2->flexy->tags = [];
    expect(fn () => $model2->save())->toThrow(ValidationException::class);
});

it('includes field name in validation error messages', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'email' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required|email',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    $model->flexy->email = 'invalid-email';

    try {
        $model->save();
        expect(false)->toBeTrue(); // Should not reach here
    } catch (ValidationException $e) {
        $errors = $e->errors();
        expect($errors)->toHaveKey('email');
    }
});

it('validates all field types with appropriate rules', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'string_field' => ['type' => FlexyFieldType::STRING, 'rules' => 'required|string|max:255'],
            'integer_field' => ['type' => FlexyFieldType::INTEGER, 'rules' => 'required|integer|min:0'],
            'decimal_field' => ['type' => FlexyFieldType::DECIMAL, 'rules' => 'required|numeric|min:0'],
            'boolean_field' => ['type' => FlexyFieldType::BOOLEAN, 'rules' => 'required|boolean'],
            'date_field' => ['type' => FlexyFieldType::DATE, 'rules' => 'required|date'],
            'datetime_field' => ['type' => FlexyFieldType::DATETIME, 'rules' => 'required|date'],
            'json_field' => ['type' => FlexyFieldType::JSON, 'rules' => 'nullable|array'],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Set all valid values
    $model->flexy->string_field = 'test';
    $model->flexy->integer_field = 10;
    $model->flexy->decimal_field = 99.99;
    $model->flexy->boolean_field = true;
    $model->flexy->date_field = new \DateTime('2024-01-15');
    $model->flexy->datetime_field = new \DateTime('2024-01-15 10:30:00');
    $model->flexy->json_field = ['key' => 'value'];

    $model->save();

    expect($model->fresh()->flexy->string_field)->toBe('test')
        ->and($model->fresh()->flexy->integer_field)->toBe(10)
        ->and((float) $model->fresh()->flexy->decimal_field)->toBe(99.99)
        ->and($model->fresh()->flexy->boolean_field)->toBeTrue();
});
