<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

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
    );
    expect($flexyModel)->toBeInstanceOf(Shape::class)
        ->and(ExampleFlexyModel::getFlexyShape('test_field')->count())->toBeInt()->toBe(1);

    ExampleFlexyModel::deleteFlexyShape('test_field');
    expect(ExampleFlexyModel::getFlexyShape('test_field'))->toBeNull();
});

it('can set get a flexy models flexy fields', function () {

    $flexyModel1 = ExampleFlexyModel::create([
        'name' => 'ExampleFlexyModel 1',
    ]);

    dump($flexyModel1->flexy->isDirty());
    dump($flexyModel1->flexy->a='a');
    dump($flexyModel1->flexy->b);
    dump($flexyModel1->flexy->b=4);
    dump($flexyModel1->flexy->b);
    dump($flexyModel1->flexy->isDirty());

    $flexyModel1->name = 'ExampleFlexyModel 1';

    $flexyModel1->save();

});

