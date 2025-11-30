# testing Specification

## Purpose
TBD - created by archiving change enhance-test-coverage. Update Purpose after archive.
## Requirements
### Requirement: Boolean Field Test Coverage
The test suite SHALL comprehensively test boolean field handling to prevent type confusion bugs.

#### Scenario: Boolean false is tested
- **WHEN** boolean false value tests are executed
- **THEN** storage, retrieval, and querying SHALL be verified
- **AND** distinction from integer 0 SHALL be tested

#### Scenario: Boolean vs integer distinction is verified
- **WHEN** boolean and integer tests are executed
- **THEN** boolean false SHALL NOT equal integer 0
- **AND** boolean true SHALL NOT equal integer 1

### Requirement: Date and DateTime Test Coverage
The test suite SHALL verify date/datetime field handling including Carbon integration and timezone support.

#### Scenario: Date storage and retrieval is tested
- **WHEN** date field tests are executed
- **THEN** Carbon date instances SHALL be stored and retrieved correctly
- **AND** date formatting SHALL be preserved

#### Scenario: Timezone handling is verified
- **WHEN** datetime with timezone tests are executed
- **THEN** timezone conversions SHALL be handled correctly

### Requirement: Edge Case Test Coverage
The test suite SHALL verify system behavior with edge cases including null, unicode, boundary values, and error scenarios.

#### Scenario: Unicode support is tested
- **WHEN** unicode character tests are executed
- **THEN** emoji, Chinese, Arabic, and other unicode SHALL be stored and retrieved correctly

#### Scenario: Boundary values are tested
- **WHEN** edge case tests are executed
- **THEN** null, empty strings, very long strings, and large numbers SHALL be handled correctly

#### Scenario: Field set edge cases are tested
- **WHEN** field set edge case tests are executed
- **THEN** concurrent operations, deletion scenarios, and assignment edge cases SHALL be verified

#### Scenario: Validation edge cases are tested
- **WHEN** validation edge case tests are executed
- **THEN** null handling, empty strings, special characters, and custom messages SHALL be verified

#### Scenario: Query edge cases are tested
- **WHEN** query edge case tests are executed
- **THEN** non-existent fields, null values, cross-field-set queries, and ordering SHALL be verified

#### Scenario: Type system edge cases are tested
- **WHEN** type system edge case tests are executed
- **THEN** large numbers, precision, circular references, and unsupported types SHALL be verified

### Requirement: Concurrent Update Test Coverage
The test suite SHALL verify system behavior under concurrent updates and race conditions.

#### Scenario: Race conditions are tested
- **WHEN** concurrent update tests are executed
- **THEN** multiple processes updating same/different fields SHALL be verified
- **AND** data consistency SHALL be maintained

#### Scenario: Concurrent field set operations are tested
- **WHEN** concurrent field set tests are executed
- **THEN** concurrent creation, deletion, and assignment operations SHALL be verified
- **AND** database constraints SHALL prevent data corruption

### Requirement: TDD Methodology
The test suite SHALL follow Test-Driven Development (TDD) principles where tests are written before or alongside implementation.

#### Scenario: Tests are written first
- **WHEN** implementing new features
- **THEN** tests SHALL be written before implementation code
- **AND** tests SHALL initially fail (red phase)
- **AND** implementation SHALL make tests pass (green phase)
- **AND** code SHALL be refactored while keeping tests green (refactor phase)

#### Scenario: Edge cases are tested
- **WHEN** edge cases are identified
- **THEN** tests SHALL be written for each edge case
- **AND** tests SHALL verify both success and failure scenarios
- **AND** tests SHALL verify error messages and exception types

### Requirement: Error Handling Test Coverage
The test suite SHALL verify all exception scenarios and error messages.

#### Scenario: FieldSetNotFoundException is tested
- **WHEN** field set not found tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include set code and model type

#### Scenario: FieldNotInSetException is tested
- **WHEN** field not in set tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include field name, set code, and available fields

#### Scenario: FieldSetInUseException is tested
- **WHEN** field set in use tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include usage count

#### Scenario: ValidationException is tested
- **WHEN** validation failure tests are executed
- **THEN** exception SHALL be thrown with correct messages
- **AND** custom validation messages SHALL be displayed

#### Scenario: FlexyFieldTypeNotAllowedException is tested
- **WHEN** unsupported type tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include the actual type name

### Requirement: Data Integrity Test Coverage
The test suite SHALL verify data integrity scenarios including foreign key constraints and cascade operations.

#### Scenario: Foreign key constraints are tested
- **WHEN** foreign key constraint tests are executed
- **THEN** invalid field_set_code assignments SHALL be prevented
- **AND** appropriate exceptions SHALL be thrown

#### Scenario: Cascade operations are tested
- **WHEN** cascade operation tests are executed
- **THEN** model deletion SHALL cascade to ff_values
- **AND** field set deletion SHALL handle ON DELETE SET NULL correctly

#### Scenario: Orphan record handling is tested
- **WHEN** orphan record tests are executed
- **THEN** orphan ff_values records SHALL be identified
- **AND** cleanup procedures SHALL be verified

### Requirement: Performance Test Coverage
The test suite SHALL verify performance characteristics for large datasets.

#### Scenario: Large field set performance is tested
- **WHEN** field set with 1000+ fields tests are executed
- **THEN** operations SHALL complete within acceptable time limits
- **AND** performance SHALL be documented

#### Scenario: Large dataset performance is tested
- **WHEN** 100,000+ models tests are executed
- **THEN** queries and operations SHALL complete within acceptable time limits
- **AND** performance SHALL be documented

### Requirement: PostgreSQL Compatibility Verification
The test suite SHALL verify all features work identically on PostgreSQL as on MySQL.

#### Scenario: PostgreSQL feature parity is tested
- **WHEN** PostgreSQL compatibility tests are executed
- **THEN** view creation, value storage, and querying SHALL work identically
- **AND** boolean handling SHALL match MySQL behavior

#### Scenario: CI/CD tests both databases
- **WHEN** CI/CD pipeline runs
- **THEN** tests SHALL execute on both MySQL and PostgreSQL in parallel
- **AND** both databases SHALL pass all tests
- **AND** test failures on either database SHALL fail the workflow

### Requirement: Coverage Metrics
The test suite SHALL achieve minimum code coverage thresholds.

#### Scenario: Line coverage threshold is met
- **WHEN** coverage report is generated
- **THEN** line coverage SHALL be at least 95%

#### Scenario: Branch coverage threshold is met
- **WHEN** coverage report is generated
- **THEN** branch coverage SHALL be at least 90%

### Requirement: CI/CD tests both databases
The CI/CD pipeline SHALL execute all tests on both MySQL and PostgreSQL databases to ensure compatibility.

#### Scenario: Tests run on MySQL in CI
- **WHEN** code is pushed to main branch or a pull request is opened
- **THEN** all tests SHALL execute against MySQL 8.0 database
- **AND** test results SHALL be reported in GitHub Actions

#### Scenario: Tests run on PostgreSQL in CI
- **WHEN** code is pushed to main branch or a pull request is opened
- **THEN** all tests SHALL execute against PostgreSQL database
- **AND** test results SHALL be reported in GitHub Actions
- **AND** both MySQL and PostgreSQL test runs SHALL pass before merge

#### Scenario: Workflow triggers on main branch push
- **WHEN** code is pushed to main branch
- **THEN** CI/CD workflow SHALL automatically trigger
- **AND** tests SHALL run on both MySQL and PostgreSQL

#### Scenario: Workflow triggers on pull request
- **WHEN** a pull request is opened or updated
- **THEN** CI/CD workflow SHALL automatically trigger
- **AND** tests SHALL run on both MySQL and PostgreSQL
- **AND** PR status SHALL reflect test results

#### Scenario: Multiple PHP versions tested
- **WHEN** CI/CD workflow runs
- **THEN** tests SHALL execute on PHP 8.3, 8.4, and 8.5
- **AND** all PHP versions SHALL be tested with both databases

#### Scenario: Multiple Laravel versions tested
- **WHEN** CI/CD workflow runs
- **THEN** tests SHALL execute on Laravel 11.x and 12.x
- **AND** all Laravel versions SHALL be tested with both databases

### Requirement: TDD Methodology
The test suite SHALL follow Test-Driven Development (TDD) principles where tests are written before or alongside implementation.

#### Scenario: Tests are written first
- **WHEN** implementing new features
- **THEN** tests SHALL be written before implementation code
- **AND** tests SHALL initially fail (red phase)
- **AND** implementation SHALL make tests pass (green phase)
- **AND** code SHALL be refactored while keeping tests green (refactor phase)

#### Scenario: Edge cases are tested
- **WHEN** edge cases are identified
- **THEN** tests SHALL be written for each edge case
- **AND** tests SHALL verify both success and failure scenarios
- **AND** tests SHALL verify error messages and exception types

### Requirement: Error Handling Test Coverage
The test suite SHALL verify all exception scenarios and error messages.

#### Scenario: FieldSetNotFoundException is tested
- **WHEN** field set not found tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include set code and model type

#### Scenario: FieldNotInSetException is tested
- **WHEN** field not in set tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include field name, set code, and available fields

#### Scenario: FieldSetInUseException is tested
- **WHEN** field set in use tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include usage count

#### Scenario: ValidationException is tested
- **WHEN** validation failure tests are executed
- **THEN** exception SHALL be thrown with correct messages
- **AND** custom validation messages SHALL be displayed

#### Scenario: FlexyFieldTypeNotAllowedException is tested
- **WHEN** unsupported type tests are executed
- **THEN** exception SHALL be thrown with correct message
- **AND** exception message SHALL include the actual type name

### Requirement: Data Integrity Test Coverage
The test suite SHALL verify data integrity scenarios including foreign key constraints and cascade operations.

#### Scenario: Foreign key constraints are tested
- **WHEN** foreign key constraint tests are executed
- **THEN** invalid field_set_code assignments SHALL be prevented
- **AND** appropriate exceptions SHALL be thrown

#### Scenario: Cascade operations are tested
- **WHEN** cascade operation tests are executed
- **THEN** model deletion SHALL cascade to ff_values
- **AND** field set deletion SHALL handle ON DELETE SET NULL correctly

#### Scenario: Orphan record handling is tested
- **WHEN** orphan record tests are executed
- **THEN** orphan ff_values records SHALL be identified
- **AND** cleanup procedures SHALL be verified

### Requirement: Performance Test Coverage
The test suite SHALL verify performance characteristics for large datasets.

#### Scenario: Large field set performance is tested
- **WHEN** field set with 1000+ fields tests are executed
- **THEN** operations SHALL complete within acceptable time limits
- **AND** performance SHALL be documented

#### Scenario: Large dataset performance is tested
- **WHEN** 100,000+ models tests are executed
- **THEN** queries and operations SHALL complete within acceptable time limits
- **AND** performance SHALL be documented

### Requirement: Local Multi-Database Test Execution
The `composer test` command SHALL execute all tests on both MySQL and PostgreSQL databases sequentially to ensure compatibility before code is pushed to CI/CD.

#### Scenario: composer test runs MySQL tests
- **WHEN** `composer test` is executed
- **THEN** tests SHALL first execute against MySQL using `phpunit.xml.dist` configuration
- **AND** test output SHALL clearly indicate MySQL is being tested
- **AND** test results SHALL be reported

#### Scenario: composer test runs PostgreSQL tests
- **WHEN** `composer test` is executed
- **THEN** tests SHALL execute against PostgreSQL using `phpunit-postgress.xml.dist` configuration after MySQL tests complete
- **AND** test output SHALL clearly indicate PostgreSQL is being tested
- **AND** test results SHALL be reported

#### Scenario: Test failure on either database fails the command
- **WHEN** `composer test` is executed
- **THEN** if MySQL tests fail, the command SHALL exit with non-zero status
- **AND** if PostgreSQL tests fail, the command SHALL exit with non-zero status
- **AND** both database test runs SHALL complete (fail-fast behavior is optional)

#### Scenario: Both databases must pass for success
- **WHEN** `composer test` is executed
- **THEN** the command SHALL only exit with zero status if both MySQL and PostgreSQL tests pass
- **AND** failure on either database SHALL cause the overall command to fail

#### Scenario: Performance tests are excluded from regular test run
- **WHEN** `composer test` is executed
- **THEN** performance tests SHALL NOT be executed
- **AND** regular test execution SHALL remain fast for quick feedback

### Requirement: Performance Test Execution
Performance tests SHALL be available for explicit execution via a dedicated command, allowing developers to validate performance characteristics when needed without slowing down regular test runs.

#### Scenario: Performance tests can be run explicitly
- **WHEN** `composer test:performance` is executed
- **THEN** performance tests SHALL execute on both MySQL and PostgreSQL sequentially
- **AND** test output SHALL clearly indicate which database is being tested
- **AND** performance test results SHALL be reported

#### Scenario: Performance tests are not skipped
- **WHEN** performance tests are executed via `composer test:performance`
- **THEN** tests SHALL NOT be skipped
- **AND** all performance test scenarios SHALL execute
- **AND** performance assertions SHALL be validated

#### Scenario: Performance test failures fail the command
- **WHEN** `composer test:performance` is executed
- **THEN** if performance tests fail on either database, the command SHALL exit with non-zero status
- **AND** both database test runs SHALL complete before reporting failure

### Requirement: Code Quality Standards
The codebase SHALL maintain high code quality standards with clean, production-ready code free of debug statements and unresolved TODOs.

#### Scenario: Debug code is removed from production
- **WHEN** code is reviewed or deployed
- **THEN** production code SHALL NOT contain debug statements (e.g., `fwrite(STDERR, ...)`, `dump()`, `dd()`)
- **AND** commented debug code SHALL be removed from source files
- **AND** debug echo/print statements SHALL be removed from test files

#### Scenario: TODO comments are resolved
- **WHEN** code is reviewed or deployed
- **THEN** TODO comments SHALL be resolved or removed
- **AND** any incomplete functionality referenced by TODOs SHALL be documented or implemented
- **AND** code SHALL not contain unplanned work items in comments

### Requirement: Test Organization and Quality
The test suite SHALL be well-organized, free of redundancy, and follow clear organizational patterns with comprehensive coverage.

#### Scenario: Tests are organized by functionality
- **WHEN** tests are reviewed or new tests are added
- **THEN** test files SHALL be organized by functionality (validation, assignment, schema, type system)
- **AND** test file names SHALL clearly indicate what is being tested
- **AND** edge case tests SHALL be included in the relevant functional test file
- **AND** "EdgeCase" SHALL NOT be used as a primary test file name

#### Scenario: Redundant tests are removed
- **WHEN** tests are consolidated
- **THEN** duplicate test scenarios across different files SHALL be identified
- **AND** only one version of each test scenario SHALL be kept
- **AND** the most comprehensive version SHALL be preserved
- **AND** removed duplicates SHALL be documented in commit messages

#### Scenario: Test coverage is maintained during consolidation
- **WHEN** test files are consolidated
- **THEN** all unique test scenarios SHALL be preserved
- **AND** test count SHALL be maintained or increased
- **AND** test coverage percentage SHALL not decrease
- **AND** all spec requirements SHALL continue to have corresponding tests

#### Scenario: Test organization follows clear patterns
- **WHEN** developers look for tests
- **THEN** test location SHALL be predictable based on functionality
- **AND** validation tests SHALL be in SchemaValidationTest
- **AND** assignment tests SHALL be in SchemaAssignmentTest
- **AND** type system tests SHALL be in TypeSystemTest
- **AND** schema edge cases SHALL be in SchemaEdgeCaseTest

