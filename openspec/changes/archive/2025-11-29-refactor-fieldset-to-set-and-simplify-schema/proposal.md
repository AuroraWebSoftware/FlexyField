
# Change: Refactor FieldSet to Schema and Simplify Database Naming

## Why
The current naming conventions create confusion and inconsistency:

**Critical Issue: Table Name Confusion**
```
ff_field_sets  →  "field sets"
ff_set_fields  →  "set fields"
```
❌ Only word order differs! Very confusing!

**Additional Issues:**
- Metadata naming inconsistency: `metadata` vs `field_metadata` in different tables
- Column reference inconsistency: `set_code` vs `field_set_code` across tables
- Composite key prevents foreign key constraints (PostgreSQL issue)
- Legacy `ff_shapes` system still exists but deprecated

This refactoring will:
- Use industry-standard "Schema" terminology: `FieldSet` → `FieldSchema`
- Eliminate table name confusion: `ff_schemas`, `ff_schema_fields`, `ff_field_values` are clearly distinct
- Standardize column naming: `schema_code` consistently used everywhere
- Enable proper foreign key constraints using schema_id
- Remove deprecated legacy shapes system entirely
- Improve semantic clarity and consistency

## What Changes

**BREAKING CHANGES:**

### Model and Class Renames
- `FieldSet` model → `FieldSchema` model
- `SetField` model → `SchemaField` model
- `Value` model → `FieldValue` model
- `FieldSetNotFoundException` → `SchemaNotFoundException`
- `FieldSetInUseException` → `SchemaInUseException`
- Method names: `createFieldSet()` → `createSchema()`, `getFieldSet()` → `getSchema()`, `getAllFieldSets()` → `getAllSchemas()`, `deleteFieldSet()` → `deleteSchema()`, `assignToFieldSet()` → `assignToSchema()`, `whereFieldSet()` → `whereSchema()`, etc.

### Database Schema Changes

**Table Renames:**
- `ff_field_sets` → `ff_schemas`
- `ff_set_fields` → `ff_schema_fields`
- `ff_values` → `ff_field_values`

**Column Renames in `ff_schemas`:**
- `set_code` → `schema_code` (for consistency)
- `metadata` unchanged (already consistent)

**Column Renames in `ff_schema_fields`:**
- `set_code` → `schema_code` (for consistency)
- `field_name` → `name` (avoid word repetition in table)
- `field_type` → `type` (avoid word repetition in table)
- `field_metadata` → `metadata` (for consistency across tables)

**Column Renames in `ff_field_values`:**
- `field_name` → `name` (avoid word repetition in table)
- `field_set_code` → `schema_code` (for consistency)

**Column Renames in `ff_view_schema`:**
- `field_name` → `name` (for consistency with ff_field_values)

**Column Renames in User Model Tables:**
- `field_set_code` → `schema_code` (in all user tables like products, users, etc.)
- This affects all tables where `AddFieldSetCodeColumn` trait was used

**Foreign Key Improvements:**
- Add `schema_id` column to `ff_schema_fields` with FK to `ff_schemas.id`
- Add `schema_id` column to `ff_field_values` with FK to `ff_schemas.id`
- Keep `schema_code` columns for backward compatibility and flexibility

**Legacy System Removal:**
- Remove `ff_shapes` table completely

### Legacy System Removal
- Delete `Shape` model
- Delete `FlexyFieldIsNotInShape` exception
- Delete `MigrateShapesToFieldSetsCommand` command
- Remove all shape-related code and documentation

## Impact
- **Affected specs:**
  - `field-set-management`: Rename to `schema-management`, model renaming, method renaming, column name updates
  - `dynamic-field-storage`: Table/column renames, shapes removal, FK additions
  - `query-integration`: Column name updates in queries
  - `type-system`: Column name updates
  - `field-validation`: Column name updates
- **Affected code:**
  - **Models**: `FieldSchema.php`, `SchemaField.php`, `FieldValue.php` (renamed and updated)
  - **Traits**: `src/Traits/Flexy.php` - method renames, column references, relationship methods, query scopes
  - **Contracts**: `src/Contracts/FlexyModelContract.php` - interface method signatures
  - **Services**: `src/FlexyField.php` - table/column references in view creation
  - **Exceptions**: `SchemaNotFoundException.php`, `SchemaInUseException.php` (renamed)
  - **Migration Helpers**: `database/migrations/Concerns/AddSchemaCodeColumn.php` (renamed, fully rewritten)
  - **Migrations**: `database/migrations/create_flexyfield_table.php` - complete schema changes
  - **Service Provider**: `src/FlexyFieldServiceProvider.php` - command registration updates
  - **Tests**: All test files - model references, column names, method calls, table names
  - **Documentation**: README, project.md, CHANGELOG, boost guidelines, all spec files
- **Breaking changes for users:**
  - **User Model Code**: All models using FlexyField must update from `FieldSet` to `FieldSchema`
  - **Method Calls**: All API method calls must use new names (e.g., `assignToSchema()` not `assignToFieldSet()`)
  - **Database Schema**: User model tables must rename `field_set_code` → `schema_code` column
  - **Query Code**: All queries using `whereFieldSet()` must change to `whereSchema()`
  - **Relationship Code**: `$model->fieldSet` must change to `$model->schema`
- **Database changes:**
  - **FlexyField Tables**: `ff_field_sets` → `ff_schemas`, `ff_set_fields` → `ff_schema_fields`, `ff_values` → `ff_field_values`, `ff_view_schema` table column updates
  - **User Model Tables**: `field_set_code` → `schema_code` in all user tables (products, users, etc.)
  - **Column Renames**: Multiple columns renamed across all tables for consistency
  - **Foreign Keys**: New `schema_id` columns with FK constraints added
  - **Removed**: `ff_shapes` table completely removed

