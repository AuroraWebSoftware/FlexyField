<?php

use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSchemaException;
use AuroraWebSoftware\FlexyField\Exceptions\FlexyFieldTypeNotAllowedException;
use AuroraWebSoftware\FlexyField\Exceptions\SchemaInUseException;
use AuroraWebSoftware\FlexyField\Exceptions\SchemaNotFoundException;

it('SchemaNotFoundException forSchemaCode creates correct message', function () {
    $exception = SchemaNotFoundException::forSchemaCode('test_schema', 'App\\Models\\Product');

    expect($exception->getMessage())
        ->toContain("Schema 'test_schema' not found")
        ->toContain("model type 'App\\Models\\Product'");
});

it('SchemaNotFoundException notAssigned creates correct message', function () {
    $exception = SchemaNotFoundException::notAssigned('App\\Models\\Product', 123);

    expect($exception->getMessage())
        ->toContain('No schema assigned')
        ->toContain('App\\Models\\Product#123')
        ->toContain('Please assign a schema first');
});

it('FieldNotInSchemaException creates correct message with available fields', function () {
    $exception = FieldNotInSchemaException::forField('invalid_field', 'test_schema', ['field1', 'field2', 'field3']);

    expect($exception->getMessage())
        ->toContain("Field 'invalid_field' is not defined")
        ->toContain("schema 'test_schema'")
        ->toContain('Available fields: field1, field2, field3');
});

it('FieldNotInSchemaException creates correct message with no available fields', function () {
    $exception = FieldNotInSchemaException::forField('invalid_field', 'test_schema', []);

    expect($exception->getMessage())
        ->toContain("Field 'invalid_field' is not defined")
        ->toContain("schema 'test_schema'")
        ->toContain('Available fields: none');
});

it('SchemaInUseException creates correct message', function () {
    $exception = SchemaInUseException::cannotDelete('test_schema', 5);

    expect($exception->getMessage())
        ->toContain("Cannot delete schema 'test_schema'")
        ->toContain('currently in use by 5 model instance(s)')
        ->toContain('Please reassign these instances');
});

it('FlexyFieldTypeNotAllowedException can be instantiated', function () {
    $exception = new FlexyFieldTypeNotAllowedException('Test message');

    expect($exception)->toBeInstanceOf(FlexyFieldTypeNotAllowedException::class)
        ->and($exception->getMessage())->toBe('Test message');
});
