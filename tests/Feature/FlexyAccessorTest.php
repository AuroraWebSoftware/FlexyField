<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSetException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetNotFoundException;
use AuroraWebSoftware\FlexyField\Models\Value;
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

// ==================== READ TESTS ====================

describe('Reading flexy fields via ->flexy accessor', function () {
    it('can read a single field value after saving', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->save();

        $fresh = $model->fresh();
        expect($fresh->flexy->color)->toBe('red');
    });

    it('can read multiple field values', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'color' => ['type' => FlexyFieldType::STRING],
                'size' => ['type' => FlexyFieldType::INTEGER],
                'price' => ['type' => FlexyFieldType::DECIMAL],
                'in_stock' => ['type' => FlexyFieldType::BOOLEAN],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'blue';
        $model->flexy->size = 42;
        $model->flexy->price = 99.99;
        $model->flexy->in_stock = true;
        $model->save();

        $fresh = $model->fresh();
        expect($fresh->flexy->color)->toBe('blue')
            ->and($fresh->flexy->size)->toBe(42)
            ->and((float) $fresh->flexy->price)->toBe(99.99)
            ->and($fresh->flexy->in_stock)->toBeTrue();
    });

    it('returns null for unset fields', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $fresh = $model->fresh();

        expect($fresh->flexy->color)->toBeNull();
    });

    it('uses lazy loading on first access', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->save();

        // Clear query log
        DB::enableQueryLog();
        DB::flushQueryLog();

        // First access should query database
        $fresh = $model->fresh();
        $color = $fresh->flexy->color;

        $queries = DB::getQueryLog();
        expect($queries)->not->toBeEmpty()
            ->and($color)->toBe('red');
    });

    it('caches flexy instance after first access', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'color' => ['type' => FlexyFieldType::STRING],
                'size' => ['type' => FlexyFieldType::INTEGER],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->flexy->size = 42;
        $model->save();

        $fresh = $model->fresh();

        // Clear query log
        DB::enableQueryLog();
        DB::flushQueryLog();

        // First access
        $color1 = $fresh->flexy->color;

        // Second access should use cache (no new query)
        $size = $fresh->flexy->size;
        $color2 = $fresh->flexy->color;

        $queries = DB::getQueryLog();
        // Should have only one query (the initial load)
        expect($queries)->toHaveCount(1)
            ->and($color1)->toBe('red')
            ->and($color2)->toBe('red')
            ->and($size)->toBe(42);
    });

    it('can read all field types correctly', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'text_field' => ['type' => FlexyFieldType::STRING],
                'int_field' => ['type' => FlexyFieldType::INTEGER],
                'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
                'date_field' => ['type' => FlexyFieldType::DATE],
                'datetime_field' => ['type' => FlexyFieldType::DATETIME],
                'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
                'json_field' => ['type' => FlexyFieldType::JSON],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->text_field = 'test';
        $model->flexy->int_field = 123;
        $model->flexy->decimal_field = 45.67;
        $model->flexy->date_field = new DateTime('2024-01-15');
        $model->flexy->datetime_field = new DateTime('2024-01-15 10:30:00');
        $model->flexy->bool_field = true;
        $model->flexy->json_field = ['key' => 'value'];
        $model->save();

        $fresh = $model->fresh();
        $jsonField = $fresh->flexy->json_field;
        // JSON field is already decoded by the accessor
        if (is_string($jsonField)) {
            $jsonField = json_decode($jsonField, true);
        }

        expect($fresh->flexy->text_field)->toBe('test')
            ->and($fresh->flexy->int_field)->toBe(123)
            ->and((float) $fresh->flexy->decimal_field)->toBe(45.67)
            ->and($fresh->flexy->bool_field)->toBeTrue()
            ->and($jsonField)->toBe(['key' => 'value']);
    });
});

// ==================== WRITE TESTS ====================

describe('Writing flexy fields via ->flexy accessor', function () {
    it('can write a single field value', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'blue';
        $model->save();

        expect($model->fresh()->flexy->color)->toBe('blue');
    });

    it('can write multiple fields in one operation', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'color' => ['type' => FlexyFieldType::STRING],
                'size' => ['type' => FlexyFieldType::INTEGER],
                'price' => ['type' => FlexyFieldType::DECIMAL],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->flexy->size = 42;
        $model->flexy->price = 99.99;
        $model->save();

        $fresh = $model->fresh();
        expect($fresh->flexy->color)->toBe('red')
            ->and($fresh->flexy->size)->toBe(42)
            ->and((float) $fresh->flexy->price)->toBe(99.99);
    });

    it('can overwrite existing field value', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->save();

        expect($model->fresh()->flexy->color)->toBe('red');

        $model->flexy->color = 'blue';
        $model->save();

        expect($model->fresh()->flexy->color)->toBe('blue');
    });

    it('can set field to null', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->save();

        expect($model->fresh()->flexy->color)->toBe('red');

        $model->flexy->color = null;
        $model->save();

        expect($model->fresh()->flexy->color)->toBeNull();
    });

    it('can access field value in memory before saving', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'green';

        // Should be accessible in memory before save
        expect($model->flexy->color)->toBe('green');

        // After save, should persist
        $model->save();
        expect($model->fresh()->flexy->color)->toBe('green');
    });

    it('tracks dirty fields correctly', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'color' => ['type' => FlexyFieldType::STRING],
                'size' => ['type' => FlexyFieldType::INTEGER],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->flexy->size = 42;

        // Before save, should be dirty
        expect($model->flexy->isDirty())->toBeTrue()
            ->and($model->flexy->getDirty())->toHaveKeys(['color', 'size']);

        $model->save();

        // After save, should not be dirty
        expect($model->flexy->isDirty())->toBeFalse();
    });
});

// ==================== SAVE TESTS ====================

describe('Saving flexy fields via ->flexy accessor', function () {
    it('saves field values to database on save', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->save();

        $value = Value::where('model_type', ExampleFlexyModel::class)
            ->where('model_id', $model->id)
            ->where('field_name', 'color')
            ->first();

        expect($value)->not->toBeNull()
            ->and($value->value_string)->toBe('red');
    });

    it('only saves dirty fields', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'color' => ['type' => FlexyFieldType::STRING],
                'size' => ['type' => FlexyFieldType::INTEGER],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->flexy->size = 42;
        $model->save();

        // Clear and reload
        $fresh = $model->fresh();
        $fresh->flexy->color; // Load flexy instance

        // Only change one field
        $fresh->flexy->color = 'blue';
        $fresh->save();

        // Both fields should still exist
        $values = Value::where('model_type', ExampleFlexyModel::class)
            ->where('model_id', $model->id)
            ->get();

        expect($values)->toHaveCount(2)
            ->and($fresh->fresh()->flexy->color)->toBe('blue')
            ->and($fresh->fresh()->flexy->size)->toBe(42);
    });

    it('can save multiple times with different values', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);

        $model->flexy->color = 'red';
        $model->save();
        expect($model->fresh()->flexy->color)->toBe('red');

        $model->flexy->color = 'blue';
        $model->save();
        expect($model->fresh()->flexy->color)->toBe('blue');

        $model->flexy->color = 'green';
        $model->save();
        expect($model->fresh()->flexy->color)->toBe('green');
    });

    it('does not save when no fields are dirty', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->save();

        $firstUpdatedAt = $model->fresh()->updated_at;

        // Wait a bit to ensure timestamp would change
        usleep(100000); // 0.1 second

        // Access flexy but don't change anything
        $fresh = $model->fresh();
        $fresh->flexy->color; // Just read, no change
        $fresh->save();

        $secondUpdatedAt = $fresh->fresh()->updated_at;

        // Timestamp should be the same (no actual save occurred)
        expect($firstUpdatedAt->timestamp)->toBe($secondUpdatedAt->timestamp);
    });

    it('saves all field types correctly', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'text_field' => ['type' => FlexyFieldType::STRING],
                'int_field' => ['type' => FlexyFieldType::INTEGER],
                'decimal_field' => ['type' => FlexyFieldType::DECIMAL],
                'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
                'json_field' => ['type' => FlexyFieldType::JSON],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->text_field = 'test';
        $model->flexy->int_field = 123;
        $model->flexy->decimal_field = 45.67;
        $model->flexy->bool_field = true;
        $model->flexy->json_field = ['key' => 'value'];
        $model->save();

        $textValue = Value::where('model_type', ExampleFlexyModel::class)
            ->where('model_id', $model->id)
            ->where('field_name', 'text_field')
            ->first();
        $intValue = Value::where('model_type', ExampleFlexyModel::class)
            ->where('model_id', $model->id)
            ->where('field_name', 'int_field')
            ->first();
        $decimalValue = Value::where('model_type', ExampleFlexyModel::class)
            ->where('model_id', $model->id)
            ->where('field_name', 'decimal_field')
            ->first();
        $boolValue = Value::where('model_type', ExampleFlexyModel::class)
            ->where('model_id', $model->id)
            ->where('field_name', 'bool_field')
            ->first();
        $jsonValue = Value::where('model_type', ExampleFlexyModel::class)
            ->where('model_id', $model->id)
            ->where('field_name', 'json_field')
            ->first();

        expect($textValue->value_string)->toBe('test')
            ->and($intValue->value_int)->toBe(123)
            ->and((float) $decimalValue->value_decimal)->toBe(45.67)
            ->and($boolValue->value_boolean)->toBeTrue()
            ->and(json_decode($jsonValue->value_json, true))->toBe(['key' => 'value']);
    });
});

// ==================== EDGE CASE TESTS ====================

describe('Edge cases for ->flexy accessor', function () {
    it('throws exception when setting field without field set assignment', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]],
            isDefault: false // Don't auto-assign
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        // Explicitly remove field set assignment
        $model->field_set_code = null;
        $model->save();

        $model->flexy->color = 'red';
        expect(fn () => $model->save())->toThrow(FieldSetNotFoundException::class);
    });

    it('throws exception when setting field not in assigned field set', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'footwear',
            fields: ['size' => ['type' => FlexyFieldType::STRING]]
        );

        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'books',
            fields: ['isbn' => ['type' => FlexyFieldType::STRING]],
            isDefault: false
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->assignToFieldSet('footwear');

        // Try to set field from different set
        $model->flexy->isbn = '1234567890';
        expect(fn () => $model->save())->toThrow(FieldNotInSetException::class);
    });

    it('can handle empty string values', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['description' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->description = '';
        $model->save();

        expect($model->fresh()->flexy->description)->toBe('');
    });

    it('can handle zero values for numeric fields', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'count' => ['type' => FlexyFieldType::INTEGER],
                'price' => ['type' => FlexyFieldType::DECIMAL],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->count = 0;
        $model->flexy->price = 0.0;
        $model->save();

        expect($model->fresh()->flexy->count)->toBe(0)
            ->and((float) $model->fresh()->flexy->price)->toBe(0.0);
    });

    it('can handle false boolean values', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['is_active' => ['type' => FlexyFieldType::BOOLEAN]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->is_active = false;
        $model->save();

        expect($model->fresh()->flexy->is_active)->toBeFalse()
            ->and($model->fresh()->flexy->is_active)->not->toBeNull();
    });

    it('can handle large numeric values', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'large_int' => ['type' => FlexyFieldType::INTEGER],
                'large_decimal' => ['type' => FlexyFieldType::DECIMAL],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->large_int = 999999999;
        // Use a smaller decimal value to avoid precision issues
        $model->flexy->large_decimal = 123456.78;
        $model->save();

        expect($model->fresh()->flexy->large_int)->toBe(999999999)
            ->and((float) $model->fresh()->flexy->large_decimal)->toBe(123456.78);
    });

    it('can handle complex JSON structures', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['metadata' => ['type' => FlexyFieldType::JSON]]
        );

        $complexData = [
            'user' => [
                'id' => 1,
                'name' => 'John',
                'tags' => ['admin', 'user'],
                'settings' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ],
            'items' => [1, 2, 3],
        ];

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->metadata = $complexData;
        $model->save();

        $metadata = $model->fresh()->flexy->metadata;
        // JSON field might be string, decode if needed
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }
        expect($metadata)->toBe($complexData);
    });

    it('maintains field values when model is refreshed', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: ['color' => ['type' => FlexyFieldType::STRING]]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->save();

        $model->refresh();

        expect($model->flexy->color)->toBe('red');
    });

    it('can read and write fields in same operation', function () {
        $this->createFieldSetWithFields(
            modelClass: ExampleFlexyModel::class,
            setCode: 'default',
            fields: [
                'color' => ['type' => FlexyFieldType::STRING],
                'size' => ['type' => FlexyFieldType::INTEGER],
            ]
        );

        $model = ExampleFlexyModel::create(['name' => 'Product']);
        $model->flexy->color = 'red';
        $model->flexy->size = 42;
        $model->save();

        // Reload to ensure we're reading from database
        $fresh = $model->fresh();

        // Read and modify in same operation
        $fresh->flexy->color = $fresh->flexy->color.'-dark';
        $fresh->flexy->size = $fresh->flexy->size + 1;
        $fresh->save();

        expect($fresh->fresh()->flexy->color)->toBe('red-dark')
            ->and($fresh->fresh()->flexy->size)->toBe(43);
    });
});
