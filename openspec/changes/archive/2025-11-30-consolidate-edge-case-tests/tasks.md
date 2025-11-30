## 1. Analyze Test Overlap
- [ ] 1.1 Map all test scenarios in `EdgeCaseTest.php` by functionality
- [ ] 1.2 Map all test scenarios in `EdgeCaseValidationTest.php` and compare with `SchemaValidationTest.php`
- [ ] 1.3 Map all test scenarios in `EdgeCaseAssignmentTest.php` and compare with `SchemaAssignmentTest.php`
- [ ] 1.4 Map all test scenarios in `EdgeCaseSchemaTest.php` and compare with `SchemaEdgeCaseTest.php`
- [ ] 1.5 Map all test scenarios in `EdgeCaseTypeSystemTest.php`
- [ ] 1.6 Create consolidation matrix showing source → target mapping

## 2. Consolidate Validation Tests
- [ ] 2.1 Review `SchemaValidationTest.php` current coverage
- [ ] 2.2 Identify unique scenarios from `EdgeCaseValidationTest.php`
- [ ] 2.3 Move non-duplicate scenarios to `SchemaValidationTest.php`
- [ ] 2.4 Remove `EdgeCaseValidationTest.php`
- [ ] 2.5 Run validation tests to ensure coverage maintained

## 3. Consolidate Assignment Tests
- [ ] 3.1 Review `SchemaAssignmentTest.php` current coverage
- [ ] 3.2 Identify unique scenarios from `EdgeCaseAssignmentTest.php`
- [ ] 3.3 Move non-duplicate scenarios to `SchemaAssignmentTest.php`
- [ ] 3.4 Remove `EdgeCaseAssignmentTest.php`
- [ ] 3.5 Run assignment tests to ensure coverage maintained

## 4. Consolidate Schema Edge Case Tests
- [ ] 4.1 Rename `EdgeCaseSchemaTest.php` → `SchemaEdgeCaseTest.php` if not already
- [ ] 4.2 Merge with existing `SchemaEdgeCaseTest.php` if both exist
- [ ] 4.3 Ensure no duplication
- [ ] 4.4 Run schema tests to ensure coverage maintained

## 5. Create and Consolidate Type System Tests
- [ ] 5.1 Create `tests/Feature/TypeSystemTest.php`
- [ ] 5.2 Move scenarios from `EdgeCaseTypeSystemTest.php`
- [ ] 5.3 Organize by type (STRING, INTEGER, DECIMAL, BOOLEAN, DATE, DATETIME, JSON)
- [ ] 5.4 Remove `EdgeCaseTypeSystemTest.php`
- [ ] 5.5 Run type tests to ensure coverage maintained

## 6. Distribute General Edge Cases
- [ ] 6.1 Review each scenario in `EdgeCaseTest.php`
- [ ] 6.2 Distribute to appropriate test files based on functionality:
  - Schema behavior → `SchemaEdgeCaseTest.php`
  - Validation → `SchemaValidationTest.php`
  - Type casting → `TypeSystemTest.php`
  - Field access → `FlexyAccessorTest.php`
- [ ] 6.3 Remove `EdgeCaseTest.php` if empty
- [ ] 6.4 Or rename to `SchemaIntegrationTest.php` if it contains integration scenarios

## 7. Add Missing Coverage
- [ ] 7.1 Review spec requirements vs test coverage
- [ ] 7.2 Add tests for `Flexy::resetFlexy()` if missing
- [ ] 7.3 Add tests for `Flexy::bootFlexy()` event listeners if missing
- [ ] 7.4 Add tests for schema_id foreign key behavior if missing

## 8. Validation
- [ ] 8.1 Run full test suite (`./vendor/bin/pest`)
- [ ] 8.2 Verify test count is maintained or increased (currently 258 passing + 4 skipped)
- [ ] 8.3 Run PHPStan to ensure no type errors
- [ ] 8.4 Verify test coverage percentage is maintained or improved
- [ ] 8.5 Validate OpenSpec with `openspec validate consolidate-edge-case-tests --strict`
- [ ] 8.6 Update test file counts in documentation if needed

