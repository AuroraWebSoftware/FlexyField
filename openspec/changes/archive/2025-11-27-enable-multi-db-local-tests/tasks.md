## 1. Implementation
- [x] 1.1 Update `composer.json` test script to run tests on both MySQL and PostgreSQL
- [x] 1.2 Ensure proper environment variable configuration for each database
- [x] 1.3 Add clear output/logging to indicate which database is being tested
- [x] 1.4 Ensure test failures on either database fail the overall command
- [x] 1.5 Add `composer test:performance` command to run performance tests explicitly
- [x] 1.6 Remove `->skip()` calls from performance tests in `FieldSetPerformanceTest.php`
- [x] 1.7 Configure performance tests to only run when explicitly invoked (not in regular test suite)
- [x] 1.8 Verify both test runs complete successfully
- [x] 1.9 Test the new `composer test` and `composer test:performance` commands locally

## 2. Validation
- [x] 2.1 Run `composer test` and verify both MySQL and PostgreSQL tests execute
- [x] 2.2 Verify test output clearly indicates which database is being tested
- [x] 2.3 Verify that a failure in either database fails the overall command
- [x] 2.4 Verify that all existing tests pass on both databases
- [x] 2.5 Verify that `composer test` does NOT run performance tests (fast feedback)
- [x] 2.6 Verify that `composer test:performance` runs performance tests on both databases
- [x] 2.7 Verify performance tests execute successfully when explicitly invoked
- [x] 2.8 Document any additional setup requirements (e.g., PostgreSQL must be running locally)

