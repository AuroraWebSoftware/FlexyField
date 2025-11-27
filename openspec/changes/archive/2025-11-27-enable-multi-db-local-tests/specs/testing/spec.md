## ADDED Requirements
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

