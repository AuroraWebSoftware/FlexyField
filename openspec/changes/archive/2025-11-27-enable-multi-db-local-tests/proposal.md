# Change: Enable Multi-Database Local Testing and Performance Tests

## Why
Currently, `composer test` only runs tests against MySQL, even though the project supports both MySQL and PostgreSQL. Developers need to manually run tests against PostgreSQL using a separate configuration file. This creates a gap between local development testing and CI/CD, which tests both databases. Additionally, performance tests are currently skipped, preventing validation of performance characteristics during development. Running tests on both databases locally ensures compatibility issues are caught before pushing to CI/CD, and making performance tests easily accessible (but opt-in) ensures performance regressions can be caught early.

## What Changes
- Modify `composer test` script to execute tests on both MySQL and PostgreSQL sequentially
- Ensure both test runs use the correct PHPUnit configuration files (`phpunit.xml.dist` for MySQL, `phpunit-postgress.xml.dist` for PostgreSQL)
- Provide clear output indicating which database is being tested
- Ensure test failures on either database fail the overall command
- Add `composer test:performance` command to run performance tests explicitly (opt-in approach)
- Remove `->skip()` from performance tests to make them runnable when explicitly invoked
- Maintain backward compatibility by allowing single-database testing via separate commands if needed
- Keep performance tests excluded from regular `composer test` to maintain fast feedback loop

## Impact
- Affected specs: testing
- Affected code: `composer.json` (test scripts), `tests/Feature/FieldSetPerformanceTest.php` (remove skip calls)
- Breaking changes: None (additive change, existing single-database commands remain available, performance tests remain opt-in)
- Developer experience: Improved - developers will catch database compatibility issues locally before CI/CD, and can run performance tests when needed without slowing down regular test runs

