# Change: Add Select Options Support

## Why
Currently, fields like `STRING` accept any free-text input. In many cases (e.g., Color, Size, Status), we want to restrict inputs to a predefined set of options to ensure data consistency and enable UI elements like Select Boxes or Radio Buttons.

## What Changes
- **Metadata Update:** Utilize the existing `metadata` JSON column in `ff_schema_fields` table.
- **New Key:** Standardize a `options` key in the metadata array.
- **Structure:** Support simple array `['Red', 'Blue']` or key-value `{'red': 'Red', 'blue': 'Blue'}`.
- **Validation:** Add a validation rule generator that checks if the input value exists in the defined `options` list.
- **Helper:** Add `getOptions()` method to `SchemaField` model for easy retrieval.

## Impact
- **Affected specs:** `field-validation`, `metadata-structure`
- **Affected code:**
    - `src/Models/SchemaField.php`: Add `getOptions()` helper.
    - `src/Services/ValidationGenerator.php`: Add `Rule::in($options)` generation logic.
- **Breaking changes:** None.
- **Database changes:** None (uses existing `metadata` column).
