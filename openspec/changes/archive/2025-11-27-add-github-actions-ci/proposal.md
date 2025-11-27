# Change: Add GitHub Actions CI/CD

## Why
Currently, tests are only run locally and there's no automated CI/CD pipeline. This means:
- Code quality cannot be verified before merging to main
- Tests are not automatically run on pull requests
- Database compatibility (MySQL and PostgreSQL) is not verified in CI
- Multiple PHP and Laravel version combinations are not tested automatically
- Breaking changes might be introduced without detection

We need a robust CI/CD pipeline that runs all tests on both MySQL and PostgreSQL databases whenever code is pushed to main or a pull request is opened.

## What Changes
- Add GitHub Actions workflow file (`.github/workflows/tests.yml`)
- Configure test matrix for PHP versions (8.3, 8.4, 8.5)
- Configure test matrix for Laravel versions (11.x, 12.x)
- Configure database services for both MySQL 8.0 and PostgreSQL
- Run tests in parallel for each database (MySQL and PostgreSQL)
- Add PHPStan static analysis job
- Add Pint code style check job
- Trigger workflow on push to main branch
- Trigger workflow on pull request events
- Configure environment variables for database connections
- Use appropriate PHPUnit configuration files for each database
- Add workflow status badge to README (optional)

## Impact
- **Affected specs**: testing (modified)
- **Affected code**:
  - `.github/workflows/tests.yml` (new file)
  - `phpunit.xml.dist` (existing MySQL config)
  - `phpunit-postgress.xml.dist` (existing PostgreSQL config)
  - `tests/TestCase.php` (may need database configuration updates)
- **Breaking changes**: None
- **Migration path**: N/A - new feature

