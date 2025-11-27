<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldIsNotInShape;
use AuroraWebSoftware\FlexyField\Models\Shape;
use AuroraWebSoftware\FlexyField\Models\Value;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleShapelyFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {

    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

});

it('can test', function () {
    expect(true)->toBeTrue();
});

it('can test set, get and delete a shape for a flexy model', function () {
    $flexyModel = ExampleFlexyModel::setFlexyShape(
        'test_field',
        FlexyFieldType::INTEGER,
        1,
        fieldMetadata: ['a' => 1, 'b' => 2],
    );
    expect($flexyModel)->toBeInstanceOf(Shape::class)
        ->and(ExampleFlexyModel::getFlexyShape('test_field')->count())->toBeInt()->toBe(1);

    ExampleFlexyModel::deleteFlexyShape('test_field');
    expect(ExampleFlexyModel::getFlexyShape('test_field'))->toBeNull();

});

it('can set and get a flexy models flexy fields', function () {

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

    //dd(ExampleFlexyModel::where('flexy_a', 2)->get());

});

it('can get a flexy models with where condition of flexy fields', function () {

    $flexyModel1 = ExampleFlexyModel::create(['name' => 'ExampleFlexyModel 1']);

    $flexyModel1->flexy->a = 1;
    $flexyModel1->flexy->b = 'tester1';
    $flexyModel1->save();

    $flexyModel2 = ExampleFlexyModel::create(['name' => 'ExampleFlexyModel 2']);

    $flexyModel2->flexy->a = 1;
    $flexyModel2->flexy->b = 'tester2';
    $flexyModel2->save();

    expect(ExampleFlexyModel::where('flexy_a', 1)->where('flexy_b', 'tester2')->get())->toHaveCount(1)
        ->and(ExampleFlexyModel::where('flexy_a', 1)->get())->toHaveCount(2);

});

it('can get exception when shape is mandatory', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    ExampleShapelyFlexyModel::$hasShape = true;
    $flexyModel1->flexy->a = '1';
    $flexyModel1->save();
})->expectException(FlexyFieldIsNotInShape::class);

it('can create shape for a model and save', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('a', FlexyFieldType::STRING, 1);

    $flexyModel1->flexy->a = 'a';
    $flexyModel1->save();

    expect(ExampleShapelyFlexyModel::getFlexyShape('a'))->toBeInstanceOf(Shape::class);
});

it('can create shape for a model and validate and throws', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('a', FlexyFieldType::INTEGER, 1, 'numeric|max:1');

    $flexyModel1->flexy->a = 'a';
    $flexyModel1->save();
})->expectException(ValidationException::class);

it('can create shape for a model and validate and save', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('a', FlexyFieldType::INTEGER, 1, 'numeric|max:7');

    $flexyModel1->flexy->a = 5;
    $flexyModel1->save();

    expect(ExampleShapelyFlexyModel::where('flexy_a', 5)->get())->toHaveCount(1);
});

it('can test set, get and delete a shape for a flexy model bool', function () {
    $flexyModel = ExampleFlexyModel::setFlexyShape(
        'test_boolean',
        FlexyFieldType::BOOLEAN,
        1,
        fieldMetadata: ['a' => 3, 'b' => true],
    );
    expect($flexyModel)->toBeInstanceOf(Shape::class)
        ->and(ExampleFlexyModel::getFlexyShape('test_boolean')->count())->toBe(1);

    ExampleFlexyModel::deleteFlexyShape('test_boolean');
    expect(ExampleFlexyModel::getFlexyShape('test_boolean'))->toBeNull();

});

it('can create shape for a model and validate and save bool', function () {
    //    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    //    ExampleShapelyFlexyModel::$hasShape = true;
    //
    //    ExampleShapelyFlexyModel::setFlexyShape('a', FlexyFieldType::BOOLEAN, 1, 'required|bool');
    //
    //    $flexyModel1->flexy->a = false;
    //    $flexyModel1->save();
    //
    //    expect(ExampleShapelyFlexyModel::where('flexy_a', false)->get())->toHaveCount(1);
    //    expect(ExampleShapelyFlexyModel::where('flexy_a', true)->get())->toHaveCount(0);
    //
    //    $flexyModel2 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 2']);
    //    ExampleShapelyFlexyModel::$hasShape = true;
    //
    //    ExampleShapelyFlexyModel::setFlexyShape('b', FlexyFieldType::BOOLEAN, 1, 'required|bool');
    //
    //    $flexyModel2->flexy->b = true;
    //    $flexyModel2->save();
    //
    //    expect(ExampleShapelyFlexyModel::where('flexy_b', true)->get())->toHaveCount(1);
    //    expect(ExampleShapelyFlexyModel::where('flexy_b', false)->get())->toHaveCount(0);

    $flexyModel3 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 3']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('c', FlexyFieldType::BOOLEAN, 1, 'required|bool');

    $flexyModel3->flexy->c = 1;
    $flexyModel3->save();

    expect(ExampleShapelyFlexyModel::where('flexy_c', 1)->get())->toHaveCount(1);
    expect(ExampleShapelyFlexyModel::where('flexy_c', 0)->get())->toHaveCount(0);

    $flexyModel4 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 4']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('d', FlexyFieldType::BOOLEAN, 1, 'required|bool');

    $flexyModel4->flexy->d = 0;
    $flexyModel4->save();

    expect(ExampleShapelyFlexyModel::where('flexy_d', 0)->get())->toHaveCount(1);
    expect(ExampleShapelyFlexyModel::where('flexy_d', 1)->get())->toHaveCount(0);
});

it('can create shape for a model and save bool', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('a', FlexyFieldType::BOOLEAN, 1);

    $flexyModel1->flexy->a = false;
    $flexyModel1->save();

    expect(ExampleShapelyFlexyModel::getFlexyShape('a'))->toBeInstanceOf(Shape::class);
});

it('can create shape for a model and save json', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('a', FlexyFieldType::JSON, 1);

    $flexyModel1->flexy->a = ['a', 'b'];
    $flexyModel1->save();

    expect(ExampleShapelyFlexyModel::getFlexyShape('a'))->toBeInstanceOf(Shape::class);

    //dd(ExampleShapelyFlexyModel::whereName('ExampleFlexyModel 1')->first()->flexy->a);

    expect(json_decode(ExampleShapelyFlexyModel::whereName('ExampleFlexyModel 1')->first()->flexy_a))->toBe(['a', 'b']);
    expect(json_decode(ExampleShapelyFlexyModel::whereName('ExampleFlexyModel 1')->first()->flexy->a))->toBe(['a', 'b']);

});

it('can get all shapes', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape('a', FlexyFieldType::INTEGER, 3, 'numeric|max:7');
    ExampleShapelyFlexyModel::setFlexyShape('b', FlexyFieldType::INTEGER, 2, 'numeric|max:7');

    //    $flexyModel1->flexy->a = 5;
    //    $flexyModel1->save();
    //
    //    dd(ExampleShapelyFlexyModel::getAllFlexyShapes());
    expect(ExampleShapelyFlexyModel::getAllFlexyShapes())->toHaveCount(2);

    ExampleShapelyFlexyModel::setFlexyShape('c', FlexyFieldType::STRING, 1, 'string|max:7');
    //    dd(ExampleShapelyFlexyModel::getAllFlexyShapes());
    expect(ExampleShapelyFlexyModel::getAllFlexyShapes())->toHaveCount(3);
});

it('can get all shapes models field_name', function () {
    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    $flexyModel2 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 2']);
    ExampleShapelyFlexyModel::$hasShape = true;

    $flexyModel1::setFlexyShape('a', FlexyFieldType::INTEGER, 3, 'numeric|max:7');
    $flexyModel2::setFlexyShape('a', FlexyFieldType::INTEGER, 2, 'numeric|max:7');

    $flexyModel1->flexy->a = 5;
    $flexyModel1->save();
    $flexyModel2->flexy->a = 1;
    $flexyModel2->save();

    //    dd(ExampleShapelyFlexyModel::getAllFlexyShapes());
    expect(ExampleShapelyFlexyModel::getAllFlexyShapes())->toHaveCount(1);

    ExampleShapelyFlexyModel::setFlexyShape('c', FlexyFieldType::STRING, 1, 'string|max:7');
    //    dd(ExampleShapelyFlexyModel::getAllFlexyShapes());
    expect(ExampleShapelyFlexyModel::getAllFlexyShapes())->toHaveCount(2);
});

it('can delete flexy values', function () {

    $flexyModel1 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 1']);
    $flexyModel2 = ExampleShapelyFlexyModel::create(['name' => 'ExampleFlexyModel 2']);
    ExampleShapelyFlexyModel::$hasShape = true;

    $flexyModel1::setFlexyShape('a', FlexyFieldType::INTEGER, 3, 'numeric|max:7');
    $flexyModel2::setFlexyShape('b', FlexyFieldType::INTEGER, 2, 'numeric|max:7');

    $flexyModel1->flexy->a = 5;
    $flexyModel1->save();
    $flexyModel2->flexy->b = 1;
    $flexyModel2->save();

    expect(Value::where('model_type', ExampleShapelyFlexyModel::getModelType())
        ->where('model_id', $flexyModel1->id)->exists())->toBeTrue();

    $flexyModel1->delete();

    expect(Value::where('model_type', ExampleShapelyFlexyModel::getModelType())
        ->where('model_id', $flexyModel1->id)->exists())->toBeFalse();

    expect(Value::where('model_type', ExampleShapelyFlexyModel::getModelType())
        ->where('model_id', $flexyModel2->id)->exists())->toBeTrue();
});

// Tests for fix-critical-bugs proposal

it('can store boolean false without converting to integer', function () {
    $flexyModel = ExampleFlexyModel::create(['name' => 'Boolean Test']);

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
    $flexyModel = ExampleFlexyModel::create(['name' => 'Boolean Test']);

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
    $flexyModel = ExampleFlexyModel::create(['name' => 'Integer Test']);

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
    $flexyModel = ExampleFlexyModel::create(['name' => 'Type Distinction Test']);

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
    $flexyModel = ExampleFlexyModel::create(['name' => 'Float Test']);

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
    $flexyModel = ExampleFlexyModel::create(['name' => 'Numeric String Test']);

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
    ExampleShapelyFlexyModel::$hasShape = true;

    ExampleShapelyFlexyModel::setFlexyShape(
        'email',
        FlexyFieldType::STRING,
        1,
        'required|email',
        ['email' => 'Please provide a valid email address']
    );

    $flexyModel = ExampleShapelyFlexyModel::create(['name' => 'Validation Test']);
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
    $flexyModel = ExampleFlexyModel::create(['name' => 'Multi-type Test']);

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
