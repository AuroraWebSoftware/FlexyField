# Change: Consolidate Edge Case Tests

## Why
The test suite contains multiple "Edge Case" test files that test similar functionality across different contexts, leading to:
- **Test duplication**: Same scenarios tested in multiple files (e.g., validation tests in both `EdgeCaseValidationTest` and `SchemaValidationTest`)
- **Unclear organization**: Hard to find where specific functionality is tested
- **Maintenance burden**: Changes require updating multiple test files
- **Inconsistent coverage**: Some edge cases tested multiple times, others not at all

The test files to consolidate:
- `EdgeCaseTest.php` (18.4KB) - General edge cases
- `EdgeCaseValidationTest.php` (16.3KB) - Validation edge cases  
- `EdgeCaseAssignmentTest.php` (19.2KB) - Assignment edge cases
- `EdgeCaseSchemaTest.php` (7.6KB) - Schema edge cases
- `EdgeCaseTypeSystemTest.php` (11.9KB) - Type system edge cases

Total: ~73KB of potentially redundant test code across 5 files.

## What Changes
Consolidate edge case tests into existing, well-organized test files by functionality:
- Move validation tests from `EdgeCaseValidationTest` → `SchemaValidationTest`
- Move assignment tests from `EdgeCaseAssignmentTest` → `SchemaAssignmentTest`
- Move schema tests from `EdgeCaseSchemaTest` → `SchemaEdgeCaseTest` (keep this one as it's well-named)
- Move type tests from `EdgeCaseTypeSystemTest` → Create new `TypeSystemTest`
- Review `EdgeCaseTest` and distribute scenarios to appropriate test files
- Remove duplicate test scenarios
- Ensure all unique edge cases are preserved
- Add missing test coverage identified during consolidation

## Impact
- **Affected specs**: testing (test organization requirement)
- **Affected files**:
  - `tests/Feature/EdgeCaseTest.php` - Review and distribute
  - `tests/Feature/EdgeCaseValidationTest.php` - Consolidate into SchemaValidationTest
  - `tests/Feature/EdgeCaseAssignmentTest.php` - Consolidate into SchemaAssignmentTest
  - `tests/Feature/EdgeCaseSchemaTest.php` - Rename to SchemaEdgeCaseTest if needed
  - `tests/Feature/EdgeCaseTypeSystemTest.php` - Move to TypeSystemTest
  - `tests/Feature/SchemaValidationTest.php` - Receive validation edge cases
  - `tests/Feature/SchemaAssignmentTest.php` - Receive assignment edge cases
  - `tests/Feature/TypeSystemTest.php` - Create and receive type edge cases
- **Breaking changes**: None (test consolidation only)
- **Risk level**: Medium (need to ensure no test coverage loss)
- **Expected outcome**: 
  - Reduce test file count by ~3-4 files
  - Improve test discoverability
  - Maintain or improve coverage (258+ passing tests)

