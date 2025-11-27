<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

it('has all required enum cases', function () {
    expect(FlexyFieldType::cases())->toHaveCount(7)
        ->and(FlexyFieldType::DATE->value)->toBe('date')
        ->and(FlexyFieldType::DATETIME->value)->toBe('datetime')
        ->and(FlexyFieldType::DECIMAL->value)->toBe('decimal')
        ->and(FlexyFieldType::INTEGER->value)->toBe('integer')
        ->and(FlexyFieldType::STRING->value)->toBe('string')
        ->and(FlexyFieldType::BOOLEAN->value)->toBe('boolean')
        ->and(FlexyFieldType::JSON->value)->toBe('json');
});

it('can get enum value as string', function () {
    expect(FlexyFieldType::STRING->value)->toBe('string')
        ->and(FlexyFieldType::INTEGER->value)->toBe('integer')
        ->and(FlexyFieldType::BOOLEAN->value)->toBe('boolean');
});

it('can compare enum cases', function () {
    expect(FlexyFieldType::STRING)->toBe(FlexyFieldType::STRING)
        ->and(FlexyFieldType::STRING)->not->toBe(FlexyFieldType::INTEGER);
});

it('can use enum in switch statement', function () {
    $type = FlexyFieldType::STRING;
    $result = match ($type) {
        FlexyFieldType::STRING => 'string',
        FlexyFieldType::INTEGER => 'integer',
        FlexyFieldType::BOOLEAN => 'boolean',
        default => 'other',
    };

    expect($result)->toBe('string');
});
