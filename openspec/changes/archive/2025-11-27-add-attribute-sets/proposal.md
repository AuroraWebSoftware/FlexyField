# Change: Add Field Sets

## Why
FlexyField currently allows only one shape (field schema) per model type - all Product instances share the same fields. This prevents real-world scenarios where different products need different fields (shoes need "size" and "color", books need "author" and "ISBN"). E-commerce platforms like Magento solve this with "field sets" where each product instance can use a different field configuration. We need this same capability.

## What Changes
- Add `ff_field_sets` table to store field set definitions per model type
- Add `ff_set_fields` table to replace `ff_shapes` with set scoping
- Add `field_set_code` column to models and `ff_values` table
- Add field set management methods to Flexy trait (create, get, delete sets)
- Add field management methods (add/remove fields to/from sets)
- Add instance assignment method (assign model to specific field set)
- Remove legacy shape methods (`setFlexyShape`, `getFlexyShape`, etc.)
- Update validation logic to use field sets instead of shapes
- Update global query scope to filter by field_set_code
- Provide data migration from `ff_shapes` to field sets with default sets

## Impact
- **Affected specs**: field-set-management (new), field-validation (modified), dynamic-field-storage (modified), query-integration (modified)
- **Affected code**:
  - `src/Traits/Flexy.php` - Replace shape methods with field set methods, update validation and queries
  - `src/Contracts/FlexyModelContract.php` - Update interface signatures
  - `src/Models/Shape.php` - Will be replaced by FieldSet and SetField models
  - `database/migrations/create_flexyfield_table.php` - Update to create new tables and columns
- **Breaking changes**:
  - `setFlexyShape()`, `getFlexyShape()`, `getAllFlexyShapes()`, `deleteFlexyShape()` removed
  - `$hasShape` static property removed
  - `ff_shapes` table dropped after migration
  - New API requires field set creation before adding fields
- **Migration path**: Automatic migration creates default field set per model type from existing shapes, assigns all instances to defaults, preserves all field values
