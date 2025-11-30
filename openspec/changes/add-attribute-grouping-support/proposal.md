# Change: Add Attribute Grouping Support

## Why
When a schema has many fields (e.g., 50+ product attributes), displaying them in a single flat list is overwhelming for users. Grouping fields (e.g., "Technical Specs", "Dimensions", "General Info") improves the UI/UX significantly.

## What Changes
- **Metadata Update:** Utilize the existing `metadata` JSON column in `ff_schema_fields` table.
- **New Key:** Standardize a `group` key in the metadata array (string value).
- **Helper:** Add `getGroup()` method to `SchemaField` model.
- **Collection Macro:** Potentially add a macro or helper to `FieldSchema` to retrieve fields grouped by this key (e.g., `$schema->getFieldsGrouped()`).

## Impact
- **Affected specs:** `metadata-structure`
- **Affected code:**
    - `src/Models/SchemaField.php`: Add `getGroup()` helper.
- **Breaking changes:** None.
- **Database changes:** None (uses existing `metadata` column).
