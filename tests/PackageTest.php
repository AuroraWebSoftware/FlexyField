<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Shape;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('migrate:fresh');
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

it('can test x', function () {



});

