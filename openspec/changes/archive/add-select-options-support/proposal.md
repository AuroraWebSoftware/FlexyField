# Change: Add Select Options Support

## Why
Currently, fields like `STRING` accept any free-text input. In many cases (e.g., Color, Size, Status), we want to restrict inputs to a predefined set of options to ensure data consistency and enable UI elements like Select Boxes or Radio Buttons. Additionally, some attributes require selecting multiple values (e.g., "Tags", "Features").

## What Changes
- **Metadata Update:** Utilize the existing `metadata` JSON column in `ff_schema_fields` table.
- **New Keys:**
    - `options`: Array of allowed values. Supports simple array `['Red', 'Blue']` or key-value `{'red': 'Red', 'blue': 'Blue'}`.
    - `multiple`: Boolean flag. If `true`, allows selecting multiple options.
- **Storage:**
    - Single select: Stored as `STRING` (or `INTEGER` etc. depending on field type).
    - Multi select: Stored as `JSON` (array of selected keys).
- **Validation:**
    - Single select: Check if value exists in `options` keys.
    - Multi select: Check if *all* values in the input array exist in `options` keys.
- **Helpers:**
    - `getOptions()`: Returns the options array.
    - `isMultiSelect()`: Returns true if `multiple` flag is set.

## Constraints
- **Multi-select Type Requirement:** Fields with `multiple: true` MUST have `FlexyFieldType::JSON` as their type.
- **Options Format:** The `options` array can be either indexed `['Red', 'Blue']` or associative `{'r': 'Red', 'b': 'Blue'}`. For associative arrays, validation checks against keys.

## Example Usage

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Single select (key-value)
Product::addFieldToSchema('electronics', 'color', FlexyFieldType::STRING, 
    metadata: ['options' => ['red' => 'Red', 'blue' => 'Blue', 'green' => 'Green']]);

// Single select (simple array)
Product::addFieldToSchema('electronics', 'size', FlexyFieldType::STRING,
    metadata: ['options' => ['S', 'M', 'L', 'XL']]);

// Multi-select (requires JSON type)
Product::addFieldToSchema('electronics', 'features', FlexyFieldType::JSON,
    metadata: [
        'options' => ['wifi', '5g', 'nfc', 'bluetooth'],
        'multiple' => true
    ]);

// Usage
$product = Product::create(['name' => 'Smartphone']);
$product->assignToSchema('electronics');
$product->flexy->color = 'red'; // Valid
$product->flexy->features = ['wifi', '5g']; // Valid (array for multi-select)
$product->save();
```

## Impact
- **Affected specs:** `field-validation`, `metadata-structure`
- **Affected code:**
    - `src/Models/SchemaField.php`: Add `getOptions()` and `isMultiSelect()` helpers, update `getValidationRulesArray()` to inject validation rules.
- **Breaking changes:** None.
- **Database changes:** None (uses existing `metadata` column).

## Notes
- Fields without `options` metadata continue to work unchanged (backward compatible).
- If `options` array is empty, no validation restriction is applied.
- If a field has `multiple: true` but is not type `JSON`, an exception should be thrown during schema field creation or validation.
