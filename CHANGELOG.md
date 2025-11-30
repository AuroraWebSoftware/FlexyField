# Changelog

All notable changes to `FlexyField` will be documented in this file.

## [Unreleased] - TBD

## [3.0.0] - 2025-11-29

### BREAKING CHANGES

#### Major Refactoring: FieldSet to FieldSchema

This release includes a major refactoring to improve naming consistency and database structure:

**Model Renames:**
- `FieldSet` → `FieldSchema`
- `SetField` → `SchemaField`
- `Value` → `FieldValue`
- `FieldSetNotFoundException` → `SchemaNotFoundException`
- `FieldSetInUseException` → `SchemaInUseException`

**Database Table Renames:**
- `ff_field_sets` → `ff_schemas`
- `ff_set_fields` → `ff_schema_fields`
- `ff_values` → `ff_field_values`

**Column Renames:**
- `set_code` → `schema_code` (in all tables)
- `field_name` → `name` (in field tables)
- `field_type` → `type` (in field tables)
- `field_metadata` → `metadata` (in field tables)
- `field_set_code` → `schema_code` (in ff_field_values)

**Method Renames:**
- `createFieldSet()` → `createSchema()`
- `getFieldSet()` → `getSchema()`
- `getAllFieldSets()` → `getAllSchemas()`
- `deleteFieldSet()` → `deleteSchema()`
- `addFieldToSet()` → `addFieldToSchema()`
- `removeFieldFromSet()` → `removeFieldFromSchema()`
- `getFieldsForSet()` → `getFieldsForSchema()`
- `assignToFieldSet()` → `assignToSchema()`
- `getFieldSetCode()` → `getSchemaCode()`
- `fieldSet()` → `schema()` (relationship method)
- `whereFieldSet()` → `whereSchema()`
- `whereInFieldSet()` → `whereInSchema()`
- `whereDefaultFieldSet()` → `whereDefaultSchema()`
- `whereHasFieldSet()` → `whereHasSchema()`
- `whereDoesntHaveFieldSet()` → `whereDoesntHaveSchema()`

**Migration Helper Renames:**
- `AddFieldSetCodeColumn` → `AddSchemaCodeColumn`

**Removed:**
- Legacy shapes system completely removed
- `FlexyFieldIsNotInShape` exception removed

**Added:**
- Foreign key constraints using `schema_id` columns
- Proper referential integrity with cascade deletes

### Migration Guide

**For Users Upgrading from 2.x to 3.0:**

1. Update your model code:
   ```php
   // Before
   $model->assignToFieldSet('footwear');
   $fieldSet = Product::getFieldSet('footwear');
   
   // After
   $model->assignToSchema('footwear');
   $schema = Product::getSchema('footwear');
   ```

2. Update your migrations:
   ```php
   // Before
   use AuroraWebSoftware\FlexyField\Database\Migrations\Concerns\AddFieldSetCodeColumn;
   $this->addFieldSetCodeColumn('products');
   
   // After
   use AuroraWebSoftware\FlexyField\Database\Migrations\Concerns\AddSchemaCodeColumn;
   $this->addSchemaCodeColumn('products');
   ```

3. Run the provided migration to update database structure:
   ```bash
   php artisan migrate
   ```

4. Update any custom queries using old table/column names

**For New Users:**

Just follow the updated documentation in README.md - all examples now use the new terminology.

### Added

- Industry-standard "Schema" terminology for better clarity
- Proper foreign key constraints for database integrity
- Clearer table and column naming
- Improved semantic consistency throughout the codebase

### Fixed

- Table name confusion between `ff_field_sets` and `ff_set_fields`
- Column naming inconsistencies
- Missing foreign key constraints in PostgreSQL
