# Change: Enhance Test Coverage

## Why
Current test coverage has critical gaps including commented-out boolean tests, no date/datetime handling tests, missing edge cases (unicode, null, large values), no concurrent update tests, and unvalidated PostgreSQL compatibility. These gaps reduce confidence in production deployments and make it difficult to catch regressions. Comprehensive test coverage is required before production readiness.

## What Changes
- Create comprehensive boolean field tests (false, true, vs integer 0/1)
- Add date/datetime handling tests with Carbon integration and timezone support
- Create edge case test suite (null, empty strings, unicode, long strings, numeric strings)
- Implement concurrent update and race condition tests
- Add PostgreSQL compatibility test suite
- Create separate GitHub Actions workflow for PostgreSQL testing
- Achieve 95%+ code coverage target
- Document supported databases (MySQL 8.0+, PostgreSQL 15+)

## Impact
- Affected specs: None (testing only, no spec changes)
- Affected code:
  - New test files: `tests/Feature/{BooleanFieldTest,DateTimeFieldTest,EdgeCaseTest,ConcurrencyTest,PostgreSQLTest}.php`
  - New CI workflow: `.github/workflows/run-tests-postgres.yml`
  - Documentation: README.md, new TESTING.md guide
- Testing infrastructure: PostgreSQL service added to CI/CD
- Coverage metrics: Target 95%+ line coverage, 90%+ branch coverage
