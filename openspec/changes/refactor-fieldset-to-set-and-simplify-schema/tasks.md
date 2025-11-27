## 1. Database Schema Updates
- [ ] 1.1 Update migration file: rename `ff_field_sets` to `ff_sets`
- [ ] 1.2 Update migration: rename `set_code` to `code` in `ff_sets` table
- [ ] 1.3 Update migration: rename columns in `ff_set_fields` (`field_name` → `name`, `field_type` → `type`, `field_metadata` → `metadata`)
- [ ] 1.4 Update migration: rename columns in `ff_values` (`field_name` → `name`, `field_set_code` → `set_code`)
- [ ] 1.5 Update migration: remove `ff_shapes` table creation
- [ ] 1.6 Update migration: update all unique constraints and indexes with new column names
- [ ] 1.7 Update `AddFieldSetCodeColumn.php` concern: update column name references

## 2. Model Refactoring
- [ ] 2.1 Rename `FieldSet.php` to `Set.php` and update class name
- [ ] 2.2 Update `Set.php`: change table name to `ff_sets`, update all column references (`set_code` → `code`)
- [ ] 2.3 Update `SetField.php`: update relationship to use `code` instead of `set_code`, update column references
- [ ] 2.4 Update `Value.php`: update relationship to use `code`/`set_code`, update column references
- [ ] 2.5 Rename `FieldSetNotFoundException.php` to `SetNotFoundException.php`
- [ ] 2.6 Rename `FieldSetInUseException.php` to `SetInUseException.php`
- [ ] 2.7 Update exception messages to use "set" instead of "field set"

## 3. Core Service Updates
- [ ] 3.1 Update `Flexy.php` trait: replace all `FieldSet` references with `Set`
- [ ] 3.2 Update `Flexy.php`: rename all methods (`createFieldSet` → `createSet`, etc.)
- [ ] 3.3 Update `Flexy.php`: update all column references (`field_name` → `name`, `field_type` → `type`, `field_metadata` → `metadata`, `field_set_code` → `set_code`, `set_code` → `code`)
- [ ] 3.4 Update `Flexy.php`: update exception references
- [ ] 3.5 Update `Flexy.php`: rename relationship method `fieldSet()` → `set()`
- [ ] 3.6 Update `FlexyField.php`: update column references in view creation (`field_name` → `name`)
- [ ] 3.7 Update `FlexyFieldServiceProvider.php`: remove `MigrateShapesToFieldSetsCommand` registration

## 4. Remove Legacy Shapes System
- [ ] 4.1 Delete `src/Models/Shape.php`
- [ ] 4.2 Delete `src/Exceptions/FlexyFieldIsNotInShape.php`
- [ ] 4.3 Delete `src/Commands/MigrateShapesToFieldSetsCommand.php`
- [ ] 4.4 Remove all shape references from codebase (grep and remove)

## 5. Test Updates
- [ ] 5.1 Rename `FieldSetTest.php` to `SetTest.php`
- [ ] 5.2 Update all test files: replace `FieldSet` with `Set`
- [ ] 5.3 Update all test files: update method names to new API
- [ ] 5.4 Update all test files: update column references in test data
- [ ] 5.5 Rename `CreatesFieldSets.php` to `CreatesSets.php` and update trait
- [ ] 5.6 Delete `FieldSetMigrationEdgeCaseTest.php`
- [ ] 5.7 Delete or update `ExampleShapelyFlexyModel.php`
- [ ] 5.8 Remove shape-related tests from `PackageTest.php`

## 6. Documentation Updates
- [ ] 6.1 Update `README.md`: replace FieldSet with Set, update examples, update table/column names, remove shapes section
- [ ] 6.2 Update `openspec/project.md`: update all references, remove shapes section
- [ ] 6.3 Update `openspec/AGENTS.md`: update references
- [ ] 6.4 Update all active `openspec/specs/*.md` files: update FieldSet to Set, update column names
- [ ] 6.5 Update archive folders in `openspec/changes/`: update references where relevant
- [ ] 6.6 Update all `docs/*.md` files: update references, remove shapes
- [ ] 6.7 Update `CHANGELOG.md`: add breaking change entry
- [ ] 6.8 Update `resources/boost/guidelines/core.blade.php`: update examples

## 7. Validation
- [ ] 7.1 Run all tests to ensure they pass
- [ ] 7.2 Run PHPStan to check for type errors
- [ ] 7.3 Run `openspec validate refactor-fieldset-to-set-and-simplify-schema --strict`
- [ ] 7.4 Verify database migration works on both MySQL and PostgreSQL
- [ ] 7.5 Test backward compatibility (ensure old column names are not referenced)

