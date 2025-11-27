<?php

use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSetException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetInUseException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetNotFoundException;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldTypeNotAllowedException;

it('FieldSetNotFoundException forSetCode creates correct message', function () {
    $exception = FieldSetNotFoundException::forSetCode('test_set', 'App\\Models\\Product');

    expect($exception->getMessage())
        ->toContain("Field set 'test_set' not found")
        ->toContain("model type 'App\\Models\\Product'");
});

it('FieldSetNotFoundException notAssigned creates correct message', function () {
    $exception = FieldSetNotFoundException::notAssigned('App\\Models\\Product', 123);

    expect($exception->getMessage())
        ->toContain('No field set assigned')
        ->toContain('App\\Models\\Product#123')
        ->toContain('Please assign a field set first');
});

it('FieldNotInSetException creates correct message with available fields', function () {
    $exception = FieldNotInSetException::forField('invalid_field', 'test_set', ['field1', 'field2', 'field3']);

    expect($exception->getMessage())
        ->toContain("Field 'invalid_field' is not defined")
        ->toContain("field set 'test_set'")
        ->toContain('Available fields: field1, field2, field3');
});

it('FieldNotInSetException creates correct message with no available fields', function () {
    $exception = FieldNotInSetException::forField('invalid_field', 'test_set', []);

    expect($exception->getMessage())
        ->toContain("Field 'invalid_field' is not defined")
        ->toContain("field set 'test_set'")
        ->toContain('Available fields: none');
});

it('FieldSetInUseException creates correct message', function () {
    $exception = FieldSetInUseException::cannotDelete('test_set', 5);

    expect($exception->getMessage())
        ->toContain("Cannot delete field set 'test_set'")
        ->toContain('currently in use by 5 model instance(s)')
        ->toContain('Please reassign these instances');
});

it('FlexyFieldTypeNotAllowedException can be instantiated', function () {
    $exception = new FlexyFieldTypeNotAllowedException('Test message');

    expect($exception)->toBeInstanceOf(FlexyFieldTypeNotAllowedException::class)
        ->and($exception->getMessage())->toBe('Test message');
});
