<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSetException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetNotFoundException;
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

        // REMOVED FOR PGSQL:         $table->foreign('field_set_code')
        // REMOVED FOR PGSQL:             ->references('set_code')
        // REMOVED FOR PGSQL:             ->on('ff_field_sets')
        // REMOVED FOR PGSQL:             ->onDelete('set null')
        // REMOVED FOR PGSQL:             ->onUpdate('cascade');
    });
});

it('applies validation rules from SetField', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'email' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required|email|max:255',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Valid email should pass
    $model->flexy->email = 'test@example.com';
    $model->save();
    expect($model->fresh()->flexy->email)->toBe('test@example.com');

    // Invalid email should fail
    $model2 = ExampleFlexyModel::create(['name' => 'Product 2']);
    $model2->assignToFieldSet('products');
    $model2->flexy->email = 'invalid-email';
    expect(fn () => $model2->save())->toThrow(ValidationException::class);
});

it('throws FieldNotInSetException when field not in set', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Shoe']);
    $model->assignToFieldSet('footwear');

    // Try to set a field that doesn't exist in the set
    $model->flexy->isbn = '1234567890';
    expect(fn () => $model->save())
        ->toThrow(FieldNotInSetException::class)
        ->and(fn () => $model->save())
        ->toThrow('Field \'isbn\' is not defined in field set \'footwear\'');
});

it('throws FieldSetNotFoundException when no set assigned', function () {
    $model = ExampleFlexyModel::create(['name' => 'Product']);

    // Try to set a field without field set assignment
    $model->flexy->test_field = 'value';
    expect(fn () => $model->save())
        ->toThrow(FieldSetNotFoundException::class)
        ->and(fn () => $model->save())
        ->toThrow('Please assign a field set first');
});

it('applies custom validation messages', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'email' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required|email',
                'messages' => [
                    'required' => 'Email is required',
                    'email' => 'Please provide a valid email address',
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
        expect($e->getMessage())->toContain('Please provide a valid email address');
    }
});

it('validates required fields', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'product_name' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Try to save without required field (but set a value first to trigger validation)
    $model->flexy->product_name = ''; // Empty string should fail 'required'
    expect(fn () => $model->save())->toThrow(ValidationException::class);

    // Set required field and save
    $model->flexy->product_name = 'Product Name';
    $model->save();
    expect($model->fresh()->flexy->product_name)->toBe('Product Name');
});

it('validates integer fields', function () {
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

    // Valid integer
    $model->flexy->quantity = 10;
    $model->save();
    expect($model->fresh()->flexy->quantity)->toBe(10);

    // Invalid: not an integer
    $model2 = ExampleFlexyModel::create(['name' => 'Product 2']);
    $model2->assignToFieldSet('products');
    $model2->flexy->quantity = 'not-a-number';
    expect(fn () => $model2->save())->toThrow(ValidationException::class);

    // Invalid: below minimum
    $model3 = ExampleFlexyModel::create(['name' => 'Product 3']);
    $model3->assignToFieldSet('products');
    $model3->flexy->quantity = 0;
    expect(fn () => $model3->save())->toThrow(ValidationException::class);
});

it('validates decimal fields', function () {
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

    // Valid decimal
    $model->flexy->price = 99.99;
    $model->save();
    expect((float) $model->fresh()->flexy->price)->toBe(99.99);

    // Invalid: negative price
    $model2 = ExampleFlexyModel::create(['name' => 'Product 2']);
    $model2->assignToFieldSet('products');
    $model2->flexy->price = -10;
    expect(fn () => $model2->save())->toThrow(ValidationException::class);
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

    // Null value should be allowed
    $model->flexy->description = null;
    $model->save();
    expect($model->fresh()->flexy->description)->toBeNull();
});

it('validates boolean fields', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: [
            'is_active' => [
                'type' => FlexyFieldType::BOOLEAN,
                'rules' => 'required|boolean',
            ],
        ]
    );

    $model = ExampleFlexyModel::create(['name' => 'Product']);
    $model->assignToFieldSet('products');

    // Valid boolean
    $model->flexy->is_active = true;
    $model->save();
    expect($model->fresh()->flexy->is_active)->toBeTrue();

    $model->flexy->is_active = false;
    $model->save();
    expect($model->fresh()->flexy->is_active)->toBeFalse();
});
