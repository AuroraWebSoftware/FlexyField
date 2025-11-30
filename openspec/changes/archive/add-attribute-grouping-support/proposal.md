# Change: Add Attribute Grouping Support

## Why
When a schema has many fields (e.g., 50+ product attributes), displaying them in a single flat list is overwhelming for users. Grouping fields (e.g., "Technical Specs", "Dimensions", "General Info") improves the UI/UX significantly.

## What Changes
- **Metadata Update:** Utilize the existing `metadata` JSON column in `ff_schema_fields` table.
- **New Key:** Standardize a `group` key in the metadata array (string value).
- **Helper:** Add `getGroup()` method to `SchemaField` model.
- **Collection Helper:** Add `getFieldsGrouped()` to `FieldSchema` to retrieve fields grouped by this key.

## Usage Example

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Define fields with groups
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'voltage',
    fieldType: FlexyFieldType::STRING,
    fieldMetadata: ['group' => 'Power Specs']
);

Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'weight_kg',
    fieldType: FlexyFieldType::DECIMAL,
    fieldMetadata: ['group' => 'Physical Dimensions']
);

// Fields without group metadata will be ungrouped
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'name',
    fieldType: FlexyFieldType::STRING
);

// Retrieve grouped fields
$schema = FieldSchema::where('schema_code', 'electronics')->first();
$grouped = $schema->getFieldsGrouped();
// Returns: ['Power Specs' => [...], 'Physical Dimensions' => [...], 'Ungrouped' => [...]]
```

## Constraints
- **Group Name:** Any string value. Empty strings (`""`) treated as ungrouped.
- **Special Characters:** Fully supported (emoji, international characters, etc.).
- **Length:** No hard limit (practical UI limit ~50 chars).
- **Case Sensitivity:** Preserved as-is. `"Power Specs"` ≠ `"power specs"`.
- **Order:**
  - Groups displayed in alphabetical order (case-insensitive).
  - "Ungrouped" fields always appear **last**.
  - Fields within each group sorted by existing `sort` column.

## Impact
- **Affected specs:** `metadata-structure`
- **Affected code:**
    - `src/Models/SchemaField.php`: Add `getGroup()` and `hasGroup()` helpers.
    - `src/Models/FieldSchema.php`: Add `getFieldsGrouped()` helper.
- **Breaking changes:** None.
- **Database changes:** None (uses existing `metadata` column).

## Requirements
- **Clean Architecture:** Keep logic simple and contained within Models. Avoid unnecessary service layers.
- **Testing:**
    - Comprehensive feature tests in `tests/Feature/AttributeGroupingTest.php`.
    - Edge case coverage (null groups, empty strings, special characters, sorting).
- **Quality:**
    - Must pass `phpstan` (Larastan) level 5+.
    - Must pass `pint` style checks.
- **Documentation:**
    - Update `README.md` with usage examples.
    - Update `docs/BEST_PRACTICES.md` with grouping best practices.
    - Update `resources/boost/guidelines/core.blade.php` for AI assistants.

## Notes
- Groups are purely metadata-driven; no database schema changes required.
- This feature is **optional** - existing schemas without groups continue to work unchanged.
- UI rendering is left to the consuming application (this package provides data structure only).
