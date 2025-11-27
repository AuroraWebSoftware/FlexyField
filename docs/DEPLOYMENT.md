# Deployment Guide

This guide covers deploying FlexyField to production environments, including pre-deployment preparation, deployment steps, rollback procedures, and post-deployment verification.

## Table of Contents

- [Pre-Deployment Checklist](#pre-deployment-checklist)
- [Environment Requirements](#environment-requirements)
- [Deployment Steps](#deployment-steps)
- [Database Migration Strategy](#database-migration-strategy)
- [Zero-Downtime Deployment](#zero-downtime-deployment)
- [Rollback Procedures](#rollback-procedures)
- [Backup Strategies](#backup-strategies)
- [Post-Deployment Verification](#post-deployment-verification)
- [Monitoring Setup](#monitoring-setup)
- [Common Deployment Issues](#common-deployment-issues)

## Pre-Deployment Checklist

### Code Readiness

- [ ] All tests passing (`./vendor/bin/pest`)
- [ ] Code review completed
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Version tagged in Git
- [ ] Dependencies updated (`composer update --no-dev`)

### Database Readiness

- [ ] Backup created and verified
- [ ] Migration tested on staging environment
- [ ] Estimated migration time calculated
- [ ] Rollback migration tested
- [ ] Database indexes reviewed
- [ ] Query performance tested with production-like data

### Performance Validation

- [ ] Load testing completed
- [ ] View recreation time measured (< 10s for medium scale)
- [ ] Query performance benchmarked
- [ ] Memory usage profiled
- [ ] N+1 query issues resolved

### Infrastructure

- [ ] Database server resources sufficient (see PERFORMANCE.md)
- [ ] Monitoring configured
- [ ] Logs configured and tested
- [ ] Health check endpoint working
- [ ] Backup automation verified

## Environment Requirements

### Minimum Requirements

```
PHP: 8.1+
Laravel: 10.0+
MySQL: 8.0+ or MariaDB: 10.5+
PostgreSQL: 13+ (alternative)
Memory: 512MB+ for application
Disk: SSD recommended
```

### Recommended for Production

```
PHP: 8.2+
Laravel: 11.0+
MySQL: 8.0+ or MariaDB: 10.11+
Memory: 2GB+ for application
CPU: 2+ cores
Disk: NVMe SSD
Database: Separate server
```

### PHP Extensions Required

```bash
# Check required extensions
php -m | grep -E "pdo|pdo_mysql|json|mbstring"
```

Required extensions:
- pdo
- pdo_mysql (or pdo_pgsql)
- json
- mbstring

## Deployment Steps

### Step 1: Pre-Deployment Backup

```bash
# Database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Or for large databases (compressed)
mysqldump -u username -p database_name | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Verify backup
gunzip -c backup_*.sql.gz | head -n 20
```

### Step 2: Enable Maintenance Mode

```bash
php artisan down --retry=60 --secret="deployment-token"

# Access during maintenance:
# https://yourapp.com/deployment-token
```

### Step 3: Pull Latest Code

```bash
git fetch origin
git checkout main
git pull origin main

# Or specific tag
git checkout v2.0.0
```

### Step 4: Install Dependencies

```bash
# Production dependencies only
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 5: Run Migrations

```bash
# Check pending migrations
php artisan migrate:status

# Run migrations
php artisan migrate --force

# If adding FlexyField for first time:
# php artisan migrate --path=vendor/aurorawebsoftware/flexyfield/database/migrations
```

### Step 6: Rebuild FlexyField View

```bash
# Force rebuild the pivot view
php artisan flexyfield:rebuild-view

# Verify view exists
php artisan tinker
>>> DB::select("SHOW TABLES LIKE 'ff_values_pivot_view'");
```

### Step 7: Optimize Application

```bash
# Optimize autoloader
composer dump-autoload --optimize

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

### Step 8: Restart Services

```bash
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Or for Apache
sudo systemctl restart apache2

# Restart queue workers
php artisan queue:restart
```

### Step 9: Disable Maintenance Mode

```bash
php artisan up
```

### Step 10: Post-Deployment Verification

Run verification checks (see [Post-Deployment Verification](#post-deployment-verification) section).

## Database Migration Strategy

### Strategy 1: Direct Migration (Small Databases)

**Best for:**
- < 100K records
- < 5 seconds migration time
- Acceptable brief downtime

```bash
php artisan down
php artisan migrate --force
php artisan flexyfield:rebuild-view
php artisan up
```

### Strategy 2: Blue-Green Deployment (Medium Databases)

**Best for:**
- 100K-1M records
- 5-60 seconds migration time
- Zero-downtime required

```bash
# 1. Deploy to green environment
# 2. Run migrations on green database
# 3. Rebuild views
# 4. Switch traffic to green
# 5. Monitor for issues
# 6. Decommission blue after verification
```

### Strategy 3: Phased Migration (Large Databases)

**Best for:**
- > 1M records
- > 60 seconds migration time
- Zero-downtime critical

```bash
# Phase 1: Deploy code (backward compatible)
git pull
composer install
php artisan config:cache

# Phase 2: Run migrations (non-blocking)
php artisan migrate --force

# Phase 3: Rebuild view (during low traffic)
# Schedule during maintenance window
php artisan flexyfield:rebuild-view

# Phase 4: Verify and monitor
```

## Zero-Downtime Deployment

### Approach 1: Load Balancer (Recommended)

```bash
# 1. Remove server1 from load balancer
# 2. Deploy to server1
# 3. Verify server1
# 4. Add server1 back to load balancer
# 5. Repeat for server2, server3, etc.
```

### Approach 2: Database-First Migration

```bash
# 1. Ensure new code is backward compatible
# 2. Run migrations (online, non-blocking)
php artisan migrate --force

# 3. Deploy application code
git pull
composer install --no-dev --optimize-autoloader

# 4. Restart PHP-FPM rolling
sudo systemctl reload php8.2-fpm

# 5. Rebuild view during low traffic
php artisan flexyfield:rebuild-view
```

### Backward Compatibility Checklist

For zero-downtime deployment, ensure:
- [ ] Database migrations are additive only (no column drops)
- [ ] New fields have default values or are nullable
- [ ] View recreation doesn't break existing queries
- [ ] Old code can run with new database schema
- [ ] New code can run with old database schema

## Rollback Procedures

### Scenario 1: Code Rollback (No Migration)

```bash
# Enable maintenance
php artisan down

# Rollback code
git checkout previous-version-tag
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache

# Restart services
sudo systemctl restart php8.2-fpm

# Disable maintenance
php artisan up
```

### Scenario 2: Migration Rollback

```bash
# Enable maintenance
php artisan down

# Rollback last migration batch
php artisan migrate:rollback --force

# Rollback specific steps
php artisan migrate:rollback --step=1 --force

# Rebuild view
php artisan flexyfield:rebuild-view

# Rollback code
git checkout previous-version-tag
composer install --no-dev --optimize-autoloader

# Restart services
sudo systemctl restart php8.2-fpm

# Disable maintenance
php artisan up
```

### Scenario 3: Database Restore (Critical Failure)

```bash
# Stop application
php artisan down
sudo systemctl stop php8.2-fpm

# Restore database backup
gunzip < backup_20240115_120000.sql.gz | mysql -u username -p database_name

# Verify restore
mysql -u username -p database_name -e "SELECT COUNT(*) FROM ff_values;"

# Restore code
git checkout last-known-good-version
composer install --no-dev --optimize-autoloader

# Start application
sudo systemctl start php8.2-fpm
php artisan up
```

### Rollback Decision Matrix

| Severity | Symptoms | Action | Downtime |
|----------|----------|--------|----------|
| **Low** | Minor bugs, visual issues | Monitor, fix forward | None |
| **Medium** | Functional issues, degraded performance | Code rollback | < 5 min |
| **High** | Critical features broken, data issues | Migration rollback | 5-15 min |
| **Critical** | Data corruption, complete failure | Database restore | 15-60 min |

## Backup Strategies

### Strategy 1: Automated Daily Backups

```bash
# Cron job (daily at 2 AM)
0 2 * * * /usr/local/bin/backup-database.sh
```

**backup-database.sh:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/database"
DB_NAME="production_db"
DB_USER="backup_user"

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Keep last 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete

# Verify backup
if [ $? -eq 0 ]; then
    echo "Backup successful: backup_$DATE.sql.gz"
else
    echo "Backup failed!" | mail -s "Database Backup Failed" admin@example.com
fi
```

### Strategy 2: Pre-Deployment Backup

```bash
# Always before deployment
mysqldump -u username -p database_name | gzip > pre_deploy_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Strategy 3: Point-in-Time Recovery

```bash
# Enable binary logs in MySQL
# /etc/mysql/my.cnf
[mysqld]
log-bin=mysql-bin
expire_logs_days=7
binlog_format=ROW
```

### Backup Verification

```bash
# Test restore on separate database
mysql -u username -p test_restore_db < backup.sql

# Verify record counts
mysql -u username -p -e "
    USE test_restore_db;
    SELECT COUNT(*) as ff_values_count FROM ff_values;
    SELECT COUNT(*) as ff_field_sets_count FROM ff_field_sets;
    SELECT COUNT(*) as ff_set_fields_count FROM ff_set_fields;
    SELECT COUNT(*) as ff_view_schema_count FROM ff_view_schema;
"
```

## Post-Deployment Verification

### Automated Verification Script

Create `deployment-verify.sh`:

```bash
#!/bin/bash

echo "=== FlexyField Deployment Verification ==="

# Check application is up
if curl -s -o /dev/null -w "%{http_code}" https://yourapp.com | grep -q "200"; then
    echo "✓ Application is responding"
else
    echo "✗ Application is not responding"
    exit 1
fi

# Check database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';"
if [ $? -eq 0 ]; then
    echo "✓ Database connection working"
else
    echo "✗ Database connection failed"
    exit 1
fi

# Check pivot view exists
VIEW_EXISTS=$(php artisan tinker --execute="echo count(DB::select(\"SHOW TABLES LIKE 'ff_values_pivot_view'\"));")
if [ "$VIEW_EXISTS" = "1" ]; then
    echo "✓ Pivot view exists"
else
    echo "✗ Pivot view missing"
    exit 1
fi

# Check FlexyField tables
TABLES=("ff_values" "ff_field_sets" "ff_set_fields" "ff_view_schema")
for table in "${TABLES[@]}"; do
    COUNT=$(php artisan tinker --execute="echo DB::table('$table')->count();")
    echo "✓ Table $table: $COUNT records"
done

# Check sample model
php artisan tinker --execute="
    \$model = App\Models\Product::first();
    if (\$model && isset(\$model->flexy)) {
        echo '✓ FlexyField integration working';
    } else {
        echo '✗ FlexyField integration failed';
        exit(1);
    }
"

# Check queue is working
php artisan queue:work --once --quiet
if [ $? -eq 0 ]; then
    echo "✓ Queue worker functioning"
else
    echo "⚠ Queue worker issue detected"
fi

echo "=== Verification Complete ==="
```

### Manual Verification Checklist

- [ ] Home page loads correctly
- [ ] Login/authentication works
- [ ] API endpoints responding
- [ ] Pivot view exists: `SHOW TABLES LIKE 'ff_values_pivot_view'`
- [ ] Sample query works:
  ```sql
  SELECT * FROM ff_values_pivot_view LIMIT 10;
  ```
- [ ] Create new model with flexy fields
- [ ] Update existing model
- [ ] Query models with flexy field filters
- [ ] Verify ff_view_schema tracking
- [ ] Check error logs for issues
- [ ] Monitor memory usage
- [ ] Check query slow log

## Monitoring Setup

### Laravel Telescope (Development/Staging)

```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### Application Monitoring

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

public function boot()
{
    // Log slow queries
    DB::listen(function ($query) {
        if ($query->time > 100) {
            Log::warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        }
    });

    // Log view recreation
    DB::listen(function ($query) {
        if (str_contains($query->sql, 'CREATE VIEW ff_values_pivot_view')) {
            Log::info('FlexyField view recreated', [
                'time' => $query->time,
                'timestamp' => now(),
            ]);
        }
    });
}
```

### Health Check Endpoint

```php
// routes/web.php
Route::get('/health', function () {
    try {
        // Check database
        DB::connection()->getPdo();

        // Check FlexyField
        $stats = [
            'status' => 'healthy',
            'database' => 'connected',
            'ff_values_count' => DB::table('ff_values')->count(),
            'ff_fields_count' => DB::table('ff_view_schema')->count(),
            'view_exists' => !empty(DB::select("SHOW TABLES LIKE 'ff_values_pivot_view'")),
        ];

        return response()->json($stats, 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 500);
    }
});
```

### External Monitoring

Configure external monitoring services:

```bash
# Uptime monitoring
- Pingdom
- UptimeRobot
- StatusCake

# Application performance monitoring (APM)
- New Relic
- DataDog
- Scout APM

# Log aggregation
- Papertrail
- Loggly
- Sentry
```

## Common Deployment Issues

### Issue 1: View Recreation Takes Too Long

**Symptoms:**
- Migration hangs during deployment
- Timeout errors

**Solution:**
```bash
# Increase PHP timeout
php -d max_execution_time=300 artisan flexyfield:rebuild-view

# Or schedule during maintenance window
php artisan down
php artisan flexyfield:rebuild-view
php artisan up
```

### Issue 2: View Not Found After Deployment

**Symptoms:**
- `Table 'ff_values_pivot_view' doesn't exist`

**Solution:**
```bash
# Rebuild view manually
php artisan flexyfield:rebuild-view

# Verify
php artisan tinker
>>> DB::select("SHOW TABLES LIKE 'ff_values_pivot_view'");
```

### Issue 3: Migration Fails Halfway

**Symptoms:**
- Some tables created, others missing
- Inconsistent database state

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Rollback if possible
php artisan migrate:rollback --force

# Or restore from backup
gunzip < backup.sql.gz | mysql -u user -p database
```

### Issue 4: Cached Config Causes Issues

**Symptoms:**
- Configuration changes not applied
- Environment variables ignored

**Solution:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Issue 5: Permission Errors

**Symptoms:**
- Cannot write to storage
- Cannot access database

**Solution:**
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Fix SELinux (if applicable)
sudo chcon -R -t httpd_sys_rw_content_t storage bootstrap/cache
```

## Deployment Environments

### Staging Environment

Use staging to test deployment:

```bash
# staging.env
APP_ENV=staging
APP_DEBUG=false
DB_DATABASE=staging_db

# Deploy to staging first
./deploy-staging.sh

# Run smoke tests
./run-smoke-tests.sh

# If successful, proceed to production
```

### Production Environment

```bash
# production.env
APP_ENV=production
APP_DEBUG=false
DB_DATABASE=production_db

# Always use tagged releases
git checkout v2.0.0

# Never use --force without testing
```

## Deployment Automation

### Laravel Forge

```php
// forge-deploy.sh (generated by Forge)
cd /home/forge/yourapp.com
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan flexyfield:rebuild-view
php artisan config:cache
php artisan route:cache
php artisan queue:restart
```

### Laravel Envoyer

Zero-downtime deployment automatically handled.

### GitHub Actions

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    tags:
      - 'v*'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/yourapp
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan flexyfield:rebuild-view
            php artisan config:cache
            sudo systemctl reload php8.2-fpm
```

## Final Deployment Checklist

Before going live:

- [ ] Backup created and verified
- [ ] Staging deployment successful
- [ ] All tests passing
- [ ] Performance benchmarks met
- [ ] Monitoring configured
- [ ] Health checks working
- [ ] Rollback procedure tested
- [ ] Team notified
- [ ] Deployment window scheduled
- [ ] Post-deployment verification plan ready

After deployment:

- [ ] Application responding
- [ ] Health check passing
- [ ] View exists and queryable
- [ ] Sample operations tested
- [ ] Logs reviewed (no errors)
- [ ] Performance metrics normal
- [ ] Monitoring active
- [ ] Team notified of success
- [ ] Documentation updated
- [ ] Post-deployment review scheduled
