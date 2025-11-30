# Change: Add UI Hints Support

## Why
Field names (e.g., `battery_capacity_mah`) are often technical and not user-friendly. Users need guidance, placeholders, and readable labels in the UI to enter data correctly.

## What Changes
- **Metadata Update:** Utilize the existing `metadata` JSON column in `ff_schema_fields` table.
- **New Keys:** Standardize the following keys:
    - `label`: Human-readable label (e.g., "Battery Capacity").
    - `placeholder`: Input placeholder text (e.g., "Enter value in mAh").
    - `hint`: Help text or tooltip (e.g., "Must be between 1000 and 5000").
- **Helpers:** Add methods to `SchemaField` to retrieve these UI attributes easily.

## Impact
- **Affected specs:** `metadata-structure`
- **Affected code:**
    - `src/Models/SchemaField.php`: Add helpers (`getLabel()`, `getPlaceholder()`, `getHint()`).
- **Breaking changes:** None.
- **Database changes:** None (uses existing `metadata` column).
