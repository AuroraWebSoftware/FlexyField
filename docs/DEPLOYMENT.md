# Deployment Guide

Essential steps for deploying FlexyField to production.

## Pre-Deployment Checklist

**Code:**
- [ ] All tests passing
- [ ] Version tagged
- [ ] Dependencies updated

**Database:**
- [ ] Backup created
- [ ] Migration tested on staging
- [ ] View recreation time measured

**Infrastructure:**
- [ ] Monitoring configured
- [ ] Health check working

## Environment Requirements

```
PHP: 8.1+ (8.2+ recommended)
Laravel: 10.0+ (11.0+ recommended)
MySQL: 8.0+ / MariaDB: 10.5+ / PostgreSQL: 13+
Memory: 512MB+ (2GB+ recommended)
Extensions: pdo, pdo_mysql, json, mbstring
```

## Deployment Steps

### 1. Backup

```bash
mysqldump -u user -p database | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### 2. Maintenance Mode

```bash
php artisan down --retry=60
```

### 3. Deploy Code

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

### 4. Run Migrations

```bash
php artisan migrate --force
php artisan flexyfield:rebuild-view
```

### 5. Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Restart Services

```bash
sudo systemctl restart php8.2-fpm
php artisan queue:restart
```

### 7. Enable Application

```bash
php artisan up
```

## Migration Strategies

**Small DB (< 100K records):** Direct migration, brief downtime OK

**Medium DB (100K - 1M):** Blue-green deployment, zero-downtime

**Large DB (> 1M):** Phased migration during low traffic

## Rollback

### Code Only

```bash
php artisan down
git checkout previous-tag
composer install --no-dev
php artisan config:cache
sudo systemctl restart php8.2-fpm
php artisan up
```

### With Migration

```bash
php artisan down
php artisan migrate:rollback --force
php artisan flexyfield:rebuild-view
git checkout previous-tag
composer install --no-dev
sudo systemctl restart php8.2-fpm
php artisan up
```

### Critical Failure (Database Restore)

```bash
php artisan down
gunzip < backup.sql.gz | mysql -u user -p database
git checkout last-known-good
composer install --no-dev
sudo systemctl restart php8.2-fpm
php artisan up
```

## Post-Deployment Verification

```bash
# Check view exists
php artisan tinker --execute="dd(DB::select(\"SHOW TABLES LIKE 'ff_values_pivot_view'\"));"

# Test flexy fields
php artisan tinker --execute="\$m = App\Models\Product::first(); dd(\$m->flexy);"

# Check logs
tail -f storage/logs/laravel.log
```

## Monitoring

### Health Check Endpoint

```php
// routes/web.php
Route::get('/health', function () {
    DB::connection()->getPdo();
    $viewExists = !empty(DB::select("SHOW TABLES LIKE 'ff_values_pivot_view'"));
    
    return response()->json([
        'status' => 'healthy',
        'view_exists' => $viewExists,
        'ff_field_values' => DB::table('ff_field_values')->count(),
    ]);
});
```

### Log Slow Queries

```php
// AppServiceProvider::boot()
DB::listen(fn($q) => $q->time > 100 && Log::warning('Slow query', ['sql' => $q->sql, 'time' => $q->time]));
```

## Common Issues

**View not found:**
```bash
php artisan flexyfield:rebuild-view
```

**Migration timeout:**
```bash
php -d max_execution_time=300 artisan flexyfield:rebuild-view
```

**Cached config:**
```bash
php artisan config:clear && php artisan config:cache
```

**Permissions:**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Automation

### Laravel Forge

```bash
cd /home/forge/app
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan flexyfield:rebuild-view
php artisan config:cache
php artisan queue:restart
```

### GitHub Actions

```yaml
- name: Deploy
  run: |
    git pull
    composer install --no-dev
    php artisan migrate --force
    php artisan flexyfield:rebuild-view
    php artisan config:cache
    sudo systemctl reload php-fpm
```
