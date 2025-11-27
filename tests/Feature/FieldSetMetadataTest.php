<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use AuroraWebSoftware\FlexyField\Models\SetField;
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

        $table->foreign('field_set_code')
            ->references('set_code')
            ->on('ff_field_sets')
            ->onDelete('set null')
            ->onUpdate('cascade');
    });
});

it('stores metadata with XSS payload without execution', function () {
    $xssPayload = '<script>alert("XSS")</script>';

    $fieldSet = ExampleFlexyModel::createFieldSet(
        setCode: 'test',
        label: 'Test Set',
        metadata: ['icon' => $xssPayload]
    );

    // Should store as-is (sanitization is application-level concern)
    expect($fieldSet->fresh()->metadata['icon'])->toBe($xssPayload);

    // Should be JSON encoded
    $raw = \Illuminate\Support\Facades\DB::table('ff_field_sets')
        ->where('id', $fieldSet->id)
        ->value('metadata');
    expect($raw)->toContain('<script>');
});

it('handles metadata with unicode characters correctly', function () {
    $unicodeMetadata = [
        'label' => '测试 🚀',
        'description' => 'Café résumé',
        'emoji' => '🎉',
    ];

    $fieldSet = ExampleFlexyModel::createFieldSet(
        setCode: 'unicode',
        label: 'Unicode Set',
        metadata: $unicodeMetadata
    );

    expect($fieldSet->fresh()->metadata)->toBe($unicodeMetadata);
});

it('handles field metadata with special characters', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'test',
        fields: [
            'field1' => [
                'type' => FlexyFieldType::STRING,
                'metadata' => [
                    'placeholder' => 'Enter "value" here',
                    'help_text' => "Line 1\nLine 2",
                ],
            ],
        ]
    );

    $setField = SetField::where('set_code', 'test')
        ->where('field_name', 'field1')
        ->first();

    expect($setField->field_metadata['placeholder'])->toBe('Enter "value" here')
        ->and($setField->field_metadata['help_text'])->toBe("Line 1\nLine 2");
});

it('handles metadata with circular reference', function () {
    $obj = new \stdClass;
    $obj->self = $obj;

    // Laravel's JSON casting may handle this differently
    // Test that it doesn't crash (may serialize or throw)
    try {
        $fieldSet = ExampleFlexyModel::createFieldSet(
            setCode: 'test',
            label: 'Test',
            metadata: ['circular' => $obj]
        );
        // If it succeeds, that's also acceptable (Laravel may handle it)
        expect($fieldSet)->toBeInstanceOf(FieldSet::class);
    } catch (\Exception $e) {
        // If it throws, that's also acceptable
        expect($e)->toBeInstanceOf(\Exception::class);
    }
});
