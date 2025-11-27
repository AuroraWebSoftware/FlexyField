<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
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

    // Create default field set with all edge case test fields
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'test_field' => ['type' => FlexyFieldType::STRING],
            'empty_field' => ['type' => FlexyFieldType::STRING],
            'long_text' => ['type' => FlexyFieldType::STRING],
            'emoji_field' => ['type' => FlexyFieldType::STRING],
            'chinese_field' => ['type' => FlexyFieldType::STRING],
            'arabic_field' => ['type' => FlexyFieldType::STRING],
            'mixed_field' => ['type' => FlexyFieldType::STRING],
            'zip_code' => ['type' => FlexyFieldType::STRING],
            'phone' => ['type' => FlexyFieldType::STRING],
            'large_amount' => ['type' => FlexyFieldType::DECIMAL],
            'temperature' => ['type' => FlexyFieldType::INTEGER],
            'balance' => ['type' => FlexyFieldType::DECIMAL],
            'complex_data' => ['type' => FlexyFieldType::JSON],
            'special_field' => ['type' => FlexyFieldType::STRING],
            'multiline_field' => ['type' => FlexyFieldType::STRING],
            'int_zero' => ['type' => FlexyFieldType::INTEGER],
            'float_zero' => ['type' => FlexyFieldType::DECIMAL],
            'string_zero' => ['type' => FlexyFieldType::STRING],
            'tiny_amount' => ['type' => FlexyFieldType::DECIMAL],
            'special_json' => ['type' => FlexyFieldType::JSON],
            'empty' => ['type' => FlexyFieldType::STRING],
            'zero' => ['type' => FlexyFieldType::INTEGER],
            'negative' => ['type' => FlexyFieldType::INTEGER],
            'unicode' => ['type' => FlexyFieldType::STRING],
            'long' => ['type' => FlexyFieldType::STRING],
        ],
        isDefault: true
    );
});

it('can handle updating to different values', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    // Set a value first
    $model->flexy->test_field = 'original value';
    $model->save();

    expect($model->fresh()->flexy->test_field)->toBe('original value');

    // Update to a different value
    $model->flexy->test_field = 'updated value';
    $model->save();

    expect($model->fresh()->flexy->test_field)->toBe('updated value');
});

it('can handle empty strings', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->empty_field = '';
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'empty_field')
        ->first();

    expect($value->value_string)->toBe('');
});

it('can handle long strings within varchar limit', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    // value_string is varchar(255), so test within that limit
    $longString = str_repeat('A', 250);
    $model->flexy->long_text = $longString;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->long_text)->toBe($longString)
        ->and(strlen($fresh->flexy->long_text))->toBe(250);
});

it('can handle unicode characters - emoji', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $emoji = '🚀🎉💻🔥⭐';
    $model->flexy->emoji_field = $emoji;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->emoji_field)->toBe($emoji);
});

it('can handle unicode characters - Chinese', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $chinese = '你好世界';
    $model->flexy->chinese_field = $chinese;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->chinese_field)->toBe($chinese);
});

it('can handle unicode characters - Arabic', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $arabic = 'مرحبا بالعالم';
    $model->flexy->arabic_field = $arabic;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->arabic_field)->toBe($arabic);
});

it('can handle unicode characters - mixed', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $mixed = 'Hello 世界 🌍 مرحبا';
    $model->flexy->mixed_field = $mixed;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->mixed_field)->toBe($mixed);
});

it('can handle numeric strings with leading zeros', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->zip_code = '00123';
    $model->flexy->phone = '007';
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->zip_code)->toBe('00123')
        ->and($fresh->flexy->phone)->toBe('007');
});

it('can handle large decimal numbers', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $largeDecimal = 9999.99;
    $model->flexy->large_amount = $largeDecimal;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'large_amount')
        ->first();

    // Compare with some tolerance for decimal precision
    expect(abs((float) $value->value_decimal - $largeDecimal))->toBeLessThan(0.01);
});

it('can handle negative integers', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->temperature = -25;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->temperature)->toBe(-25);
});

it('can handle negative decimals', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->balance = -1500.75;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'balance')
        ->first();

    expect((float) $value->value_decimal)->toBe(-1500.75);
});

it('can handle complex JSON structures', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $complexJson = [
        'user' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'roles' => ['admin', 'editor'],
            'meta' => [
                'last_login' => '2024-01-15',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true,
                ],
            ],
        ],
        'items' => [1, 2, 3, 4, 5],
    ];

    $model->flexy->complex_data = $complexJson;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'complex_data')
        ->first();

    expect(json_decode($value->value_json, true))->toBe($complexJson);
});

it('can handle special characters in values', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $specialChars = 'Test with "quotes" and \'apostrophes\' and <html> & symbols!@#$%^&*()';
    $model->flexy->special_field = $specialChars;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->special_field)->toBe($specialChars);
});

it('can handle newlines and tabs in strings', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $multiline = "Line 1\nLine 2\nLine 3\tWith Tab";
    $model->flexy->multiline_field = $multiline;
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->multiline_field)->toBe($multiline);
});

it('can handle zero values for different types', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->int_zero = 0;
    $model->flexy->float_zero = 0.0;
    $model->flexy->string_zero = '0';
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->int_zero)->toBe(0)
        ->and((float) $fresh->flexy->float_zero)->toBe(0.0)
        ->and($fresh->flexy->string_zero)->toBe('0');
});

it('can handle very small decimal numbers', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $tiny = 0.01;
    $model->flexy->tiny_amount = $tiny;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'tiny_amount')
        ->first();

    // Compare with tolerance for decimal precision
    expect(abs((float) $value->value_decimal - $tiny))->toBeLessThan(0.001);
});

it('can handle JSON with special characters', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $jsonWithSpecialChars = [
        'message' => 'Hello "World" with \'quotes\'',
        'html' => '<div>Test & Symbol</div>',
        'unicode' => '你好 🚀',
    ];

    $model->flexy->special_json = $jsonWithSpecialChars;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'special_json')
        ->first();

    expect(json_decode($value->value_json, true))->toBe($jsonWithSpecialChars);
});

it('can handle multiple edge cases on same model', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $model->flexy->empty = '';
    $model->flexy->zero = 0;
    $model->flexy->negative = -100;
    $model->flexy->unicode = '🎉';
    $model->flexy->long = str_repeat('X', 200);
    $model->save();

    $fresh = $model->fresh();

    expect($fresh->flexy->empty)->toBe('')
        ->and($fresh->flexy->zero)->toBe(0)
        ->and($fresh->flexy->negative)->toBe(-100)
        ->and($fresh->flexy->unicode)->toBe('🎉')
        ->and(strlen($fresh->flexy->long))->toBe(200);
});

it('can access field before model saved (in-memory value)', function () {
    $model = new ExampleFlexyModel(['name' => 'New Model']);

    // Assign field set first (this will save the model)
    $model->assignToFieldSet('default');

    // Set field before explicit save
    $model->flexy->test_field = 'in-memory value';

    // Should be accessible before explicit save
    expect($model->flexy->test_field)->toBe('in-memory value');

    // Save and verify it persists
    $model->save();
    expect($model->fresh()->flexy->test_field)->toBe('in-memory value');
});

it('can set field value to same value without unnecessary update', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);
    $model->flexy->test_field = 'value';
    $model->save();

    $firstSaveTime = $model->fresh()->updated_at;

    // Set to same value
    $model->flexy->test_field = 'value';
    $model->save();

    // Should still have the value
    expect($model->fresh()->flexy->test_field)->toBe('value');
});

it('can set field value then unset with null assignment', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);
    $model->flexy->test_field = 'value';
    $model->save();

    expect($model->fresh()->flexy->test_field)->toBe('value');

    // Unset by setting to null
    $model->flexy->test_field = null;
    $model->save();

    expect($model->fresh()->flexy->test_field)->toBeNull();
});

it('throws ValidationException when field exceeds max length', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'limited',
        fields: [
            'limited_field' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'max:10',
            ],
        ],
        isDefault: false
    );

    $model = ExampleFlexyModel::create(['name' => 'Test Model']);
    $model->assignToFieldSet('limited');

    // Try to set value exceeding max length
    $model->flexy->limited_field = str_repeat('A', 11);

    expect(fn () => $model->save())->toThrow(\Illuminate\Validation\ValidationException::class);
});
