# EdgeCaseTest Documentation

## Purpose

The `EdgeCaseTest` file contains comprehensive tests for edge cases in the FlexyField system. These tests verify that the system handles unusual or boundary conditions gracefully, ensuring robust behavior in production environments.

## Test Coverage

### 1. Null Value Tests

These tests verify that null values are handled correctly for all field types:

- `it handles null values for string fields correctly`
- `it handles null values for integer fields correctly`
- `it handles null values for decimal fields correctly`
- `it handles null values for boolean fields correctly`
- `it handles null values for date fields correctly`
- `it handles null values for datetime fields correctly`
- `it handles null values for JSON fields correctly`

### 2. Empty String Tests

These tests verify that empty strings are handled correctly:

- `it handles empty strings for string fields correctly`
- `it handles empty strings for JSON fields correctly`

### 3. Boundary Value Tests

These tests verify that boundary values are handled correctly for numeric fields:

- `it handles maximum integer values correctly`
- `it handles minimum integer values correctly`
- `it handles maximum decimal values correctly`
- `it handles minimum decimal values correctly`
- `it handles decimal precision correctly`

### 4. Special Character and Unicode Tests

These tests verify that special characters and unicode text are handled correctly:

- `it handles special characters in string fields correctly`
- `it handles unicode characters in string fields correctly`
- `it handles newlines and tabs in string fields correctly`

### 5. Concurrent Operation Tests

These tests verify that the system handles concurrent operations correctly:

- `it handles concurrent operations on the same record correctly`
- `it handles concurrent field assignments correctly`

### 6. Invalid Field Type Assignment Tests

These tests verify that the system handles invalid type assignments gracefully:

- `it rejects invalid string assignments to integer fields`
- `it rejects invalid boolean assignments to string fields`
- `it rejects invalid date assignments to string fields`

### 7. Additional Edge Cases

These tests cover additional edge cases:

- `it handles very long strings correctly`
- `it handles zero values correctly`

## Key Test Scenarios

### Database Constraints

The tests respect database constraints such as:
- Column name conventions (`value_int` instead of `value_integer`)
- Decimal precision limits (2 decimal places)
- String length limits (255 characters)
- JSON encoding for JSON fields

### Type Conversion

The tests verify that the system handles type conversions appropriately:
- Converting non-numeric strings to integers (results in 0)
- Converting booleans to strings (true becomes "1")
- Converting dates to strings (uses Y-m-d H:i:s format)

### Data Integrity

The tests ensure data integrity by:
- Verifying values are stored correctly in the database
- Confirming values are retrieved correctly
- Testing concurrent operations don't corrupt data

## Running the Tests

To run these tests:

```bash
# Run only EdgeCaseTest
vendor/bin/pest --configuration=phpunit.xml.dist tests/Feature/EdgeCaseTest.php

# Run all tests
composer test
```

## Adding New Edge Cases

When adding new edge case tests:

1. Follow the existing naming convention: `it handles [scenario] correctly`
2. Include both storage and retrieval verification
3. Respect database constraints
4. Test with realistic values that might occur in production
5. Add documentation for the new test case here
