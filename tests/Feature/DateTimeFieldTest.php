<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\Value;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleShapelyFlexyModel;
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

it('can store and retrieve date value', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $date = new DateTime('2024-01-15');
    $model->flexy->birth_date = $date;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'birth_date')
        ->first();

    expect($value->value_datetime)->not->toBeNull()
        ->and($value->value_datetime)->toContain('2024-01-15');
});

it('can store and retrieve datetime value', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $datetime = new DateTime('2024-01-15 14:30:00');
    $model->flexy->created_at_custom = $datetime;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'created_at_custom')
        ->first();

    expect($value->value_datetime)->not->toBeNull()
        ->and($value->value_datetime)->toContain('2024-01-15')
        ->and($value->value_datetime)->toContain('14:30:00');
});

it('can query models by date value', function () {
    $model1 = ExampleFlexyModel::create(['name' => 'January']);
    $model1->flexy->event_date = new DateTime('2024-01-15');
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'February']);
    $model2->flexy->event_date = new DateTime('2024-02-20');
    $model2->save();

    $results = ExampleFlexyModel::where('flexy_event_date', '>=', '2024-02-01')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('February');
});

it('can query date range', function () {
    $model1 = ExampleFlexyModel::create(['name' => 'Event 1']);
    $model1->flexy->event_date = new DateTime('2024-01-15');
    $model1->save();

    $model2 = ExampleFlexyModel::create(['name' => 'Event 2']);
    $model2->flexy->event_date = new DateTime('2024-02-20');
    $model2->save();

    $model3 = ExampleFlexyModel::create(['name' => 'Event 3']);
    $model3->flexy->event_date = new DateTime('2024-03-10');
    $model3->save();

    $results = ExampleFlexyModel::whereBetween('flexy_event_date', ['2024-01-01', '2024-02-28'])->get();

    expect($results)->toHaveCount(2);
});

it('can validate date fields with shapes', function () {
    ExampleShapelyFlexyModel::$hasShape = true;
    ExampleShapelyFlexyModel::setFlexyShape('birth_date', FlexyFieldType::DATE, 1, 'required|date');

    $model = ExampleShapelyFlexyModel::create(['name' => 'Test Model']);
    $model->flexy->birth_date = new DateTime('1990-05-15');
    $model->save();

    expect($model->fresh()->flexy->birth_date)->not->toBeNull();
});

it('stores datetime with different times correctly', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $morning = new DateTime('2024-01-15 08:30:00');
    $evening = new DateTime('2024-01-15 20:45:00');

    $model->flexy->morning_time = $morning;
    $model->flexy->evening_time = $evening;
    $model->save();

    $morningValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'morning_time')
        ->first();

    $eveningValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'evening_time')
        ->first();

    expect($morningValue->value_datetime)->toContain('08:30')
        ->and($eveningValue->value_datetime)->toContain('20:45');
});

it('can update datetime values', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $oldDate = new DateTime('2024-01-15');
    $model->flexy->updated_date = $oldDate;
    $model->save();

    $newDate = new DateTime('2024-02-20');
    $model->flexy->updated_date = $newDate;
    $model->save();

    $value = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'updated_date')
        ->first();

    expect($value->value_datetime)->toContain('2024-02-20');
});

it('handles past and future dates', function () {
    $model = ExampleFlexyModel::create(['name' => 'Test Model']);

    $pastDate = new DateTime('1990-01-01');
    $futureDate = new DateTime('2050-12-31');

    $model->flexy->past_date = $pastDate;
    $model->flexy->future_date = $futureDate;
    $model->save();

    $fresh = $model->fresh();

    $pastValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'past_date')
        ->first();

    $futureValue = Value::where('model_type', ExampleFlexyModel::getModelType())
        ->where('model_id', $model->id)
        ->where('field_name', 'future_date')
        ->first();

    expect($pastValue->value_datetime)->toContain('1990-01-01')
        ->and($futureValue->value_datetime)->toContain('2050-12-31');
});
