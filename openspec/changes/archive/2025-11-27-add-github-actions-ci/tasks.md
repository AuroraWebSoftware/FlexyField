## 1. Implementation
- [x] 1.1 Create `.github/workflows` directory structure
- [x] 1.2 Create `tests.yml` workflow file with basic structure
- [x] 1.3 Configure PHP version matrix (8.3, 8.4, 8.5)
- [x] 1.4 Configure Laravel version matrix (11.x, 12.x)
- [x] 1.5 Add MySQL 8.0 service configuration
- [x] 1.6 Add PostgreSQL service configuration
- [x] 1.7 Configure environment variables for MySQL connection
- [x] 1.8 Configure environment variables for PostgreSQL connection
- [x] 1.9 Add test job for MySQL using `phpunit.xml.dist`
- [x] 1.10 Add test job for PostgreSQL using `phpunit-postgress.xml.dist`
- [x] 1.11 Configure workflow triggers (push to main, pull_request)
- [x] 1.12 Add dependency installation step (composer install)
- [x] 1.13 Add database migration step before tests (migrations run automatically in tests via Artisan::call('migrate:fresh'))
- [x] 1.14 Test workflow locally using act (optional) or push to test branch
- [ ] 1.15 Verify both database tests pass in CI (requires push to GitHub)

## 2. Code Quality Checks
- [x] 2.1 Add PHPStan static analysis job
- [x] 2.2 Add Pint code style check job

## 3. Documentation
- [ ] 3.1 Update README.md with CI status badge (optional)
- [ ] 3.2 Document workflow behavior in project.md if needed

