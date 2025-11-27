# Change: Refactor FieldSet to Set and Simplify Database Schema

## Why
The current naming conventions are verbose and inconsistent. The term "FieldSet" is redundant (a "set" already implies a collection of fields), and database column names like `field_name`, `field_type`, `field_metadata`, and `field_set_code` are unnecessarily long. Additionally, the legacy `ff_shapes` system is deprecated and should be completely removed to reduce maintenance burden and confusion.

This refactoring will:
- Simplify API naming: `FieldSet` → `Set`, `createFieldSet()` → `createSet()`, etc.
- Shorten database column names for better readability and performance
- Remove deprecated legacy shapes system entirely
- Improve consistency across the codebase

## What Changes

**BREAKING CHANGES:**

### Model and Class Renames
- `FieldSet` model → `Set` model
- `FieldSetNotFoundException` → `SetNotFoundException`
- `FieldSetInUseException` → `SetInUseException`
- Method names: `createFieldSet()` → `createSet()`, `getFieldSet()` → `getSet()`, `getAllFieldSets()` → `getAllSets()`, `deleteFieldSet()` → `deleteSet()`, `assignToFieldSet()` → `assignToSet()`, `whereFieldSet()` → `whereSet()`, etc.

### Database Schema Changes
- Table rename: `ff_field_sets` → `ff_sets`
- Column renames in `ff_sets`: `set_code` → `code`
- Column renames in `ff_set_fields`: `field_name` → `name`, `field_type` → `type`, `field_metadata` → `metadata`
- Column renames in `ff_values`: `field_name` → `name`, `field_set_code` → `set_code`
- Remove `ff_shapes` table completely

### Legacy System Removal
- Delete `Shape` model
- Delete `FlexyFieldIsNotInShape` exception
- Delete `MigrateShapesToFieldSetsCommand` command
- Remove all shape-related code and documentation

## Impact
- **Affected specs:**
  - `field-set-management`: Model renaming, method renaming, column name updates
  - `dynamic-field-storage`: Table/column renames, shapes removal
  - `query-integration`: Column name updates in queries
  - `type-system`: Column name updates
  - `field-validation`: Column name updates
- **Affected code:**
  - All model files (`Set.php`, `SetField.php`, `Value.php`)
  - `src/Traits/Flexy.php`: Method renames, column references
  - `src/FlexyField.php`: Column references in view creation
  - `database/migrations/create_flexyfield_table.php`: Schema changes
  - All test files: Model references, column names, method calls
  - All documentation files: API references, examples, table/column names
- **Breaking changes:**
  - All user code using `FieldSet` must be updated to `Set`
  - All method calls must use new names
  - Database migration required for existing installations
  - All column references in user code must be updated
- **Database changes:**
  - Table rename: `ff_field_sets` → `ff_sets`
  - Multiple column renames across tables
  - Removal of `ff_shapes` table

