# Change: Remove Legacy Shapes Table and Related Code

## Why
The `ff_shapes` table was the legacy system for defining flexy field schemas before Field Sets were introduced. Field Sets have fully replaced shapes functionality, providing better instance-level field configuration. The shapes table, migration command, and related code are no longer needed and create maintenance overhead. Removing them simplifies the codebase and eliminates confusion about which system to use.

## What Changes
- Remove `ff_shapes` table creation from migration
- Remove `Shape` model class (`src/Models/Shape.php`)
- Remove `MigrateShapesToFieldSetsCommand` command class
- Remove command registration from service provider
- Remove all `ff_shapes` table references from tests
- Remove `ExampleShapelyFlexyModel` test model (if unused)
- Remove all `ff_shapes` references from documentation
- Update `openspec/project.md` to remove legacy table documentation
- **BREAKING**: Database migration change - existing installations need to drop the table manually
- **BREAKING**: Removes migration command - users must have already migrated before upgrading

## Impact
- Affected specs: dynamic-field-storage
- Affected code:
  - `database/migrations/create_flexyfield_table.php` (remove table creation)
  - `src/Models/Shape.php` (delete file)
  - `src/Commands/MigrateShapesToFieldSetsCommand.php` (delete file)
  - `src/FlexyFieldServiceProvider.php` (remove command registration)
  - `tests/Feature/FieldSetMigrationEdgeCaseTest.php` (remove or update tests)
  - `tests/PackageTest.php` (remove Shape import and skipped tests)
  - `tests/Models/ExampleShapelyFlexyModel.php` (delete if unused)
  - Documentation files (README.md, docs/*) (update references)
  - `openspec/project.md` (remove legacy table documentation)
- Database changes: Remove `ff_shapes` table from migration
- Breaking changes: **BREAKING** - Existing installations must drop the table manually. Migration command will no longer be available.

