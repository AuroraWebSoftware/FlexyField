<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldIsNotInShape;
use AuroraWebSoftware\FlexyField\Models\Shape;
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
