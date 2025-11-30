## 1. Database Schema Updates
- [ ] 1.1 Update migration file: rename `ff_field_sets` to `ff_schemas`
- [ ] 1.2 Update migration: rename `ff_set_fields` to `ff_schema_fields`
- [ ] 1.3 Update migration: rename `ff_values` to `ff_field_values`
- [ ] 1.4 Update migration: rename `set_code` to `schema_code` in `ff_schemas` table
- [ ] 1.5 Add migration: add `schema_id` column to `ff_schema_fields` (nullable, then populate, then NOT NULL)
- [ ] 1.6 Add migration: add FK constraint `ff_schema_fields.schema_id` → `ff_schemas.id` (CASCADE)
- [ ] 1.7 Update migration: rename `set_code` to `schema_code` in `ff_schema_fields`
- [ ] 1.8 Update migration: rename columns in `ff_schema_fields` (`field_name` → `name`, `field_type` → `type`, `field_metadata` → `metadata`)
- [ ] 1.9 Add migration: add `schema_id` column to `ff_field_values` (nullable, then populate)
- [ ] 1.10 Add migration: add FK constraint `ff_field_values.schema_id` → `ff_schemas.id` (SET NULL)
- [ ] 1.11 Update migration: rename `field_set_code` to `schema_code` in `ff_field_values`
- [ ] 1.12 Update migration: rename `field_name` to `name` in `ff_field_values`
- [ ] 1.13 Update migration: remove `ff_shapes` table creation
- [ ] 1.14 Update migration: update all unique constraints and indexes with new column names
- [ ] 1.15 Update `AddFieldSetCodeColumn.php` concern: rename to `AddSchemaCodeColumn.php` and update column references

## 2. Model Refactoring
- [ ] 2.1 Rename `FieldSet.php` to `FieldSchema.php` and update class name
- [ ] 2.2 Update `FieldSchema.php`: change table name to `ff_schemas`, update all column references (`set_code` → `schema_code`)
- [ ] 2.3 Rename `SetField.php` to `SchemaField.php` and update class name
- [ ] 2.4 Update `SchemaField.php`: change table name to `ff_schema_fields`, update relationship to use `schema_code`/`schema_id`, update column references
- [ ] 2.5 Rename `Value.php` to `FieldValue.php` and update class name
- [ ] 2.6 Update `FieldValue.php`: change table name to `ff_field_values`, update relationship to use `schema_code`/`schema_id`, update column references
- [ ] 2.7 Rename `FieldSetNotFoundException.php` to `SchemaNotFoundException.php` and update class name
- [ ] 2.8 Rename `FieldSetInUseException.php` to `SchemaInUseException.php` and update class name
- [ ] 2.9 Update exception messages to use "schema" instead of "field set"

## 3. Core Service Updates
- [ ] 3.1 Update `Flexy.php` trait: replace all `FieldSet` references with `FieldSchema`
- [ ] 3.2 Update `Flexy.php`: rename all methods (`createFieldSet` → `createSchema`, `getFieldSet` → `getSchema`, `getAllFieldSets` → `getAllSchemas`, `deleteFieldSet` → `deleteSchema`, `assignToFieldSet` → `assignToSchema`, `whereFieldSet` → `whereSchema`, etc.)
- [ ] 3.3 Update `Flexy.php`: update all column references (`field_name` → `name`, `field_type` → `type`, `field_metadata` → `metadata`, `field_set_code` → `schema_code`, `set_code` → `schema_code`)
- [ ] 3.4 Update `Flexy.php`: update exception references
- [ ] 3.5 Update `Flexy.php`: rename relationship method `fieldSet()` → `schema()`
- [ ] 3.6 Update `Flexy.php`: add logic to maintain both `schema_id` and `schema_code` when assigning schemas
- [ ] 3.7 Update `FlexyField.php`: update table references (`ff_field_sets` → `ff_schemas`, `ff_values` → `ff_field_values`)
- [ ] 3.8 Update `FlexyField.php`: update column references in view creation (`field_name` → `name`)
- [ ] 3.9 Update `FlexyFieldServiceProvider.php`: remove `MigrateShapesToFieldSetsCommand` registration
- [ ] 3.10 Update `FlexyModelContract.php`: rename interface methods to use "schema" terminology

## 4. Remove Legacy Shapes System
- [ ] 4.1 Delete `src/Models/Shape.php`
- [ ] 4.2 Delete `src/Exceptions/FlexyFieldIsNotInShape.php`
- [ ] 4.3 Delete `src/Commands/MigrateShapesToFieldSetsCommand.php`
- [ ] 4.4 Remove all shape references from codebase (grep and remove)

## 5. Test Updates
- [ ] 5.1 Rename `FieldSetTest.php` to `FieldSchemaTest.php`
- [ ] 5.2 Update all test files: replace `FieldSet` with `FieldSchema`, `SetField` with `SchemaField`, `Value` with `FieldValue`
- [ ] 5.3 Update all test files: update method names to new API (`createFieldSet` → `createSchema`, etc.)
- [ ] 5.4 Update all test files: update column references in test data (`field_name` → `name`, `field_set_code` → `schema_code`, etc.)
- [ ] 5.5 Update all test files: update table name references
- [ ] 5.6 Rename `CreatesFieldSets.php` to `CreatesSchemas.php` (test helper trait)
- [ ] 5.7 Delete `FieldSetMigrationEdgeCaseTest.php`
- [ ] 5.8 Delete or update `ExampleShapelyFlexyModel.php`
- [ ] 5.9 Remove shape-related tests from `PackageTest.php`
- [ ] 5.10 Add tests for schema_id FK constraints

## 6. Documentation Updates
- [ ] 6.1 Update `README.md`: replace FieldSet with FieldSchema, update examples, update table/column names, remove shapes section
- [ ] 6.2 Update `openspec/project.md`: update all references, change "Field Sets" to "Schemas", update table/column names, remove shapes section
- [ ] 6.3 Update `openspec/AGENTS.md`: update references if any
- [ ] 6.4 Rename spec folder: `openspec/specs/field-set-management` → `openspec/specs/schema-management`
- [ ] 6.5 Update all active `openspec/specs/*.md` files: update FieldSet to FieldSchema, update table/column names, update method names
- [ ] 6.6 Update archive folders in `openspec/changes/`: update references where relevant
- [ ] 6.7 Update all `docs/*.md` files if they exist: update references, remove shapes
- [ ] 6.8 Update `CHANGELOG.md`: add breaking change entry with migration guide
- [ ] 6.9 Update `resources/boost/guidelines/core.blade.php`: update examples and terminology

## 7. Validation
- [ ] 7.1 Run all tests to ensure they pass
- [ ] 7.2 Run PHPStan to check for type errors
- [ ] 7.3 Run Laravel Pint to ensure code style consistency
- [ ] 7.4 Run `openspec validate refactor-fieldset-to-set-and-simplify-schema --strict`
- [ ] 7.5 Verify database migration works on both MySQL and PostgreSQL
- [ ] 7.6 Test FK constraints work correctly (cascade deletes, set null on delete)
- [ ] 7.7 Verify pivot view recreation works with new table/column names
- [ ] 7.8 Test backward compatibility (ensure no old column names are referenced)
- [ ] 7.9 Verify all documentation examples are accurate