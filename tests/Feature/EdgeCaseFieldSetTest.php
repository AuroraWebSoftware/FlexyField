<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetInUseException;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
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

        $table->foreign('field_set_code')
            ->references('set_code')
            ->on('ff_field_sets')
            ->onDelete('set null')
            ->onUpdate('cascade');
    });
});

it('prevents concurrent field set creation with same set_code', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'duplicate',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    expect(fn () => ExampleFlexyModel::createFieldSet(
        'duplicate',
        'Duplicate Set',
        null,
        null,
        false
    ))->toThrow(\Exception::class);
});

it('throws FieldSetInUseException when deleting set with instances', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'in_use',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    // Create 3 instances
    for ($i = 1; $i <= 3; $i++) {
        $model = ExampleFlexyModel::create(['name' => "Model {$i}"]);
        $model->assignToFieldSet('in_use');
    }

    expect(fn () => ExampleFlexyModel::deleteFieldSet('in_use'))
        ->toThrow(FieldSetInUseException::class)
        ->and(fn () => ExampleFlexyModel::deleteFieldSet('in_use'))
        ->toThrow('currently in use by 3 model instance(s)');
});

it('allows deleting field set with no instances', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'unused',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $result = ExampleFlexyModel::deleteFieldSet('unused');

    expect($result)->toBeTrue()
        ->and(FieldSet::where('set_code', 'unused')->exists())->toBeFalse();
});

it('handles force delete via DB sets field_set_code to null', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'to_delete',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToFieldSet('to_delete');

    // Force delete via DB (bypassing application check)
    DB::table('ff_field_sets')->where('set_code', 'to_delete')->delete();

    // Refresh model
    $model->refresh();

    expect($model->field_set_code)->toBeNull();
});

it('allows deleting default field set when no instances', function () {
    $fieldSet = ExampleFlexyModel::createFieldSet(
        'default_set',
        'Default Set',
        null,
        null,
        true
    );

    $result = ExampleFlexyModel::deleteFieldSet('default_set');

    expect($result)->toBeTrue()
        ->and(FieldSet::where('set_code', 'default_set')->exists())->toBeFalse();
});

it('throws FieldSetNotFoundException when accessing fields after set deleted', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'temp_set',
        fields: ['field1' => ['type' => FlexyFieldType::STRING]]
    );

    $model = ExampleFlexyModel::create(['name' => 'Test']);
    $model->assignToFieldSet('temp_set');

    // Force delete via DB
    DB::table('ff_field_sets')->where('set_code', 'temp_set')->delete();

    // Try to set a field
    $model->flexy->field1 = 'value';
    expect(fn () => $model->save())->toThrow(\Exception::class);
});

it('handles invalid metadata JSON encoding', function () {
    // Create a circular reference (cannot be JSON encoded)
    $circular = new \stdClass;
    $circular->self = $circular;

    expect(fn () => ExampleFlexyModel::createFieldSet(
        'test',
        'Test',
        null,
        ['circular' => $circular],
        false
    ))->toThrow(\Exception::class);
});

it('returns null for non-existent field set', function () {
    $result = ExampleFlexyModel::getFieldSet('non_existent');

    expect($result)->toBeNull();
});

it('returns empty collection for model with no field sets', function () {
    $sets = ExampleFlexyModel::getAllFieldSets();

    expect($sets)->toBeEmpty()
        ->and($sets->count())->toBe(0);
});

it('updates is_default flag correctly when updating field set', function () {
    $fieldSet1 = ExampleFlexyModel::createFieldSet('set1', 'Set 1', null, null, true);
    $fieldSet2 = ExampleFlexyModel::createFieldSet('set2', 'Set 2', null, null, false);

    // Update set2 to be default
    $fieldSet2->is_default = true;
    $fieldSet2->save();

    expect($fieldSet2->fresh()->is_default)->toBeTrue()
        ->and($fieldSet1->fresh()->is_default)->toBeFalse();
});

it('handles getUsageCount for field set with no usage', function () {
    $fieldSet = ExampleFlexyModel::createFieldSet('unused', 'Unused', null, null, false);

    expect($fieldSet->getUsageCount(ExampleFlexyModel::class))->toBe(0)
        ->and($fieldSet->isInUse(ExampleFlexyModel::class))->toBeFalse();
});
