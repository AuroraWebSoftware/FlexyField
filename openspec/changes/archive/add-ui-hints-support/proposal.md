# Change: Add UI Hints Support

## Why
Field names (e.g., `battery_capacity_mah`) are often technical and not user-friendly. Users need guidance, placeholders, and readable labels in the UI to enter data correctly.

## What Changes
- **New Column:** Add `label` column to `ff_schema_fields` for human-readable display names.
- **Metadata Update:** Use `metadata` JSON for optional UI decorations:
    - `placeholder`: Input placeholder text
    - `hint`: Help text or tooltip
- **Helpers:** Add methods to `SchemaField` to retrieve UI attributes.

## Usage Example

```php
// Define field with UI hints
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'battery_capacity_mah',
    fieldType: FlexyFieldType::INTEGER,
    label: 'Battery Capacity',
    fieldMetadata: [
        'placeholder' => 'Enter mAh',
        'hint' => 'Max 5000mAh'
    ]
);

// Usage
$field = SchemaField::where('name', 'battery_capacity_mah')->first();
echo $field->getLabel();        // "Battery Capacity"
echo $field->getPlaceholder();  // "Enter mAh"
echo $field->getHint();         // "Max 5000mAh"

// If label is null, falls back to name
$field->label = null;
echo $field->getLabel();        // "battery_capacity_mah"
```

## Impact
- **Affected specs:** `metadata-structure`
- **Affected code:**
    - Migration: Add `label` column
    - `src/Models/SchemaField.php`: Add `getLabel()`, `getPlaceholder()`, `getHint()`
- **Breaking changes:** None (label column is nullable)
- **Database changes:** New `label` column in `ff_schema_fields`

## Requirements
- **Clean Architecture:** Simple helper methods, no complex logic
- **Testing:**
    - Comprehensive tests in `tests/Feature/UIHintsTest.php`
    - Test label fallback behavior
    - Test null/empty values
- **Quality:**
    - Must pass `phpstan` (Larastan)
    - Must pass `pint`
