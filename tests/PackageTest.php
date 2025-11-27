<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;
use AuroraWebSoftware\FlexyField\Models\Value;
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

it('can test', function () {
    expect(true)->toBeTrue();
});

// DEPRECATED: Old shape API removed, use field sets instead
it('can test set, get and delete a shape for a flexy model', function () {
    // This test uses deprecated shape API - skip for now
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can set and get a flexy models flexy fields', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'a' => ['type' => FlexyFieldType::STRING],
            'b' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $flexyModel1 = ExampleFlexyModel::create(['name' => 'ExampleFlexyModel 1']);

    $flexyModel1->flexy->a = '1';
    $flexyModel1->save();

    expect($flexyModel1->flexy->a)->toBe('1');

    $flexyModel1->flexy->a = '2';
    $flexyModel1->save();

    expect($flexyModel1->flexy->a)->toBe('2');

    $flexyModel1->flexy->b = '1';
    $flexyModel1->save();

    expect($flexyModel1->flexy->b)->toBe('1');
});

it('can get a flexy models with where condition of flexy fields', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'a' => ['type' => FlexyFieldType::INTEGER],
            'b' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $flexyModel1 = ExampleFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    $flexyModel1->flexy->a = 1;
    $flexyModel1->flexy->b = 'tester1';
    $flexyModel1->save();

    $flexyModel2 = ExampleFlexyModel::create(['name' => 'ExampleFlexyModel 2']);
    $flexyModel2->flexy->a = 1;
    $flexyModel2->flexy->b = 'tester2';
    $flexyModel2->save();

    // Recreate view to include fields
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    expect(ExampleFlexyModel::where('flexy_a', 1)->where('flexy_b', 'tester2')->get())->toHaveCount(1)
        ->and(ExampleFlexyModel::where('flexy_a', 1)->get())->toHaveCount(2);
});

// DEPRECATED: Old shape API removed
it('can get exception when shape is mandatory', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can create shape for a model and save', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can create shape for a model and validate and throws', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can create shape for a model and validate and save', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can test set, get and delete a shape for a flexy model bool', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can create shape for a model and validate and save bool', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can create shape for a model and validate and save bool - OLD', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can create shape for a model and save bool', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can create shape for a model and save json', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can get all shapes', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can get all shapes models field_name', function () {
    $this->markTestSkipped('Shape API deprecated, use field sets instead');
})->skip('Shape API deprecated');

it('can delete flexy values', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'a' => ['type' => FlexyFieldType::INTEGER],
            'b' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $flexyModel1 = ExampleFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    $flexyModel2 = ExampleFlexyModel::create(['name' => 'ExampleFlexyModel 2']);

    $flexyModel1->flexy->a = 5;
    $flexyModel1->save();
    $flexyModel2->flexy->b = 1;
    $flexyModel2->save();

    expect(Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel1->id)->exists())->toBeTrue();

    $flexyModel1->delete();

    expect(Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel1->id)->exists())->toBeFalse();

    expect(Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel2->id)->exists())->toBeTrue();
});

// Tests for fix-critical-bugs proposal

it('can store boolean false without converting to integer', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['is_active' => ['type' => FlexyFieldType::BOOLEAN]]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Boolean Test']);
    $flexyModel->assignToFieldSet('default');

    $flexyModel->flexy->is_active = false;
    $flexyModel->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'is_active')
        ->first();

    expect($value->value_boolean)->toBe(false)
        ->and($value->value_int)->toBeNull()
        ->and($flexyModel->fresh()->flexy->is_active)->toBe(false);
});

it('can store boolean true without converting to integer', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['is_active' => ['type' => FlexyFieldType::BOOLEAN]]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Boolean Test']);
    $flexyModel->assignToFieldSet('default');

    $flexyModel->flexy->is_active = true;
    $flexyModel->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'is_active')
        ->first();

    expect($value->value_boolean)->toBe(true)
        ->and($value->value_int)->toBeNull()
        ->and($flexyModel->fresh()->flexy->is_active)->toBe(true);
});

it('can store integer zero without converting to boolean', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['quantity' => ['type' => FlexyFieldType::INTEGER]]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Integer Test']);
    $flexyModel->assignToFieldSet('default');

    $flexyModel->flexy->quantity = 0;
    $flexyModel->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'quantity')
        ->first();

    expect($value->value_int)->toBe(0)
        ->and($value->value_boolean)->toBeNull()
        ->and($flexyModel->fresh()->flexy->quantity)->toBe(0);
});

it('can distinguish between boolean false and integer 0', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'bool_field' => ['type' => FlexyFieldType::BOOLEAN],
            'int_field' => ['type' => FlexyFieldType::INTEGER],
        ]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Type Distinction Test']);
    $flexyModel->assignToFieldSet('default');

    // Set boolean false
    $flexyModel->flexy->bool_field = false;
    $flexyModel->flexy->int_field = 0;
    $flexyModel->save();

    $boolValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'bool_field')
        ->first();

    $intValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'int_field')
        ->first();

    expect($boolValue->value_boolean)->toBe(false)
        ->and($boolValue->value_int)->toBeNull()
        ->and($intValue->value_int)->toBe(0)
        ->and($intValue->value_boolean)->toBeNull();
});

it('can store float values as decimal', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: ['price' => ['type' => FlexyFieldType::DECIMAL]]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Float Test']);
    $flexyModel->assignToFieldSet('default');

    $flexyModel->flexy->price = 19.99;
    $flexyModel->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'price')
        ->first();

    expect($value->value_decimal)->toBe('19.99')
        ->and($value->value_int)->toBeNull()
        ->and((float) $flexyModel->fresh()->flexy->price)->toBe(19.99);
});

it('can preserve numeric strings with leading zeros', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'zip_code' => ['type' => FlexyFieldType::STRING],
            'product_code' => ['type' => FlexyFieldType::STRING],
        ]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Numeric String Test']);
    $flexyModel->assignToFieldSet('default');

    $flexyModel->flexy->zip_code = '007';
    $flexyModel->flexy->product_code = '00123';
    $flexyModel->save();

    $zipValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'zip_code')
        ->first();

    $productValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->where('field_name', 'product_code')
        ->first();

    expect($zipValue->value_string)->toBe('007')
        ->and($productValue->value_string)->toBe('00123')
        ->and($flexyModel->fresh()->flexy->zip_code)->toBe('007')
        ->and($flexyModel->fresh()->flexy->product_code)->toBe('00123');
});

it('can use custom validation messages correctly', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'email' => [
                'type' => FlexyFieldType::STRING,
                'rules' => 'required|email',
                'messages' => ['email' => 'Please provide a valid email address'],
            ],
        ]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Validation Test']);
    $flexyModel->flexy->email = 'invalid-email';

    try {
        $flexyModel->save();
        expect(false)->toBeTrue('Should have thrown validation exception');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('email')
            ->and($e->errors()['email'][0])->toContain('Please provide a valid email address');
    }
});

it('stores different types correctly in appropriate columns', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'default',
        fields: [
            'bool_val' => ['type' => FlexyFieldType::BOOLEAN],
            'int_val' => ['type' => FlexyFieldType::INTEGER],
            'float_val' => ['type' => FlexyFieldType::DECIMAL],
            'string_val' => ['type' => FlexyFieldType::STRING],
            'json_val' => ['type' => FlexyFieldType::JSON],
        ]
    );

    $flexyModel = ExampleFlexyModel::create(['name' => 'Multi-type Test']);
    $flexyModel->assignToFieldSet('default');

    $flexyModel->flexy->bool_val = true;
    $flexyModel->flexy->int_val = 42;
    $flexyModel->flexy->float_val = 3.14;
    $flexyModel->flexy->string_val = 'hello';
    $flexyModel->flexy->json_val = ['key' => 'value'];
    $flexyModel->save();

    $values = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $flexyModel->id)
        ->get()
        ->keyBy('field_name');

    expect($values['bool_val']->value_boolean)->toBe(true)
        ->and($values['bool_val']->value_int)->toBeNull()
        ->and($values['int_val']->value_int)->toBe(42)
        ->and($values['int_val']->value_boolean)->toBeNull()
        ->and($values['float_val']->value_decimal)->toBe('3.14')
        ->and($values['string_val']->value_string)->toBe('hello')
        ->and(json_decode($values['json_val']->value_json, true))->toBe(['key' => 'value']);
});
