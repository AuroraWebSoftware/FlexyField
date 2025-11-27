# Implementation Tasks

## 1. Remove Database Table
- [ ] 1.1 Remove `ff_shapes` table creation from `database/migrations/create_flexyfield_table.php`
- [ ] 1.2 Remove `ff_shapes` table drop from migration `down()` method
- [ ] 1.3 Verify migration runs successfully without shapes table

## 2. Remove Model Class
- [ ] 2.1 Delete `src/Models/Shape.php` file
- [ ] 2.2 Search for any remaining imports or references to Shape model
- [ ] 2.3 Remove Shape model from any autoload or service provider registrations

## 3. Remove Migration Command
- [ ] 3.1 Delete `src/Commands/MigrateShapesToFieldSetsCommand.php` file
- [ ] 3.2 Remove command registration from `src/FlexyFieldServiceProvider.php`
- [ ] 3.3 Verify command is no longer available via `php artisan list`

## 4. Update Tests
- [ ] 4.1 Remove `Shape` import from `tests/PackageTest.php`
- [ ] 4.2 Remove all skipped shape-related tests from `tests/PackageTest.php`
- [ ] 4.3 Update or remove `tests/Feature/FieldSetMigrationEdgeCaseTest.php` (remove `ff_shapes` table usage)
- [ ] 4.4 Delete `tests/Models/ExampleShapelyFlexyModel.php` if unused
- [ ] 4.5 Search for any other test files referencing `ff_shapes` or `Shape`
- [ ] 4.6 Ensure all tests pass after removals

## 5. Update Documentation
- [ ] 5.1 Remove `ff_shapes` references from `README.md`
- [ ] 5.2 Update `openspec/project.md` to remove legacy table documentation
- [ ] 5.3 Update `docs/TROUBLESHOOTING.md` to remove shapes-related troubleshooting
- [ ] 5.4 Update `docs/DEPLOYMENT.md` to remove shapes table references
- [ ] 5.5 Update `docs/PERFORMANCE.md` to remove shapes references
- [ ] 5.6 Update `docs/BEST_PRACTICES.md` to remove shapes migration guidance

## 6. Update Specifications
- [ ] 6.1 Update `openspec/specs/dynamic-field-storage/spec.md` to remove migration requirements
- [ ] 6.2 Remove all references to shapes migration from specs
- [ ] 6.3 Verify spec validation passes

## 7. Code Cleanup
- [ ] 7.1 Search entire codebase for any remaining `ff_shapes` references
- [ ] 7.2 Search entire codebase for any remaining `Shape` class references
- [ ] 7.3 Search entire codebase for `migrate-shapes` command references
- [ ] 7.4 Remove all found references

## 8. Validation
- [ ] 8.1 Run all tests to ensure nothing breaks
- [ ] 8.2 Run PHPStan to check for type errors
- [ ] 8.3 Run `openspec validate remove-shapes-table --strict`
- [ ] 8.4 Verify migration can run on fresh database
- [ ] 8.5 Verify migration rollback works correctly

