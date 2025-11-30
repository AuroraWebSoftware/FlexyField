# Performance Guide

Performance characteristics and optimization strategies for FlexyField.

## Performance Characteristics

### Read Performance (1M rows)

```
Single model with 5 fields:     15-25ms
WHERE on indexed field:         30-50ms
Eager load 100 models:          150-300ms
```

### Write Performance

**v2.0+ (Optimized):**
- Single save (existing field): 5-15ms
- Single save (new field): 50-100ms
- Bulk 100 updates (same fields): 2-5s (1 view recreation)

**Performance improvement: ~98% vs v1.0**

## Field Count Recommendations

**Small (✅ Recommended):** 1-20 fields, up to 100K models
**Medium (✅ Supported):** 20-50 fields, 100K-1M models  
**Large (⚠️ Evaluate):** 50-100 fields, 1M-10M models - needs optimization
**Very Large (❌ Avoid):** 100+ fields, 10M+ models - use document DB instead

## Query Optimization

### Indexing

```php
// Add indexes for frequently queried fields
Schema::table('ff_field_values', function (Blueprint $table) {
    $table->index('value_string');
    $table->index(['name', 'value_string']);
    $table->index('value_datetime');
});
```

### Query Tips

```php
// ✅ Good: Trailing wildcard
Product::where('flexy_name', 'like', 'product%')->get();

// ❌ Bad: Leading wildcard (can't use index)
Product::where('flexy_name', 'like', '%product%')->get();

// ✅ Select specific fields only
Product::select('id', 'name', 'flexy_color')->get();
```

## View Recreation

**Smart Optimization (v2.0+):**
- Only recreates view when NEW fields added
- 1000 updates = 1-2 recreations (not 1000!)
- Tracked via database metadata (`information_schema`)
- Only recreates view when a *new* field name is introduced
- Existing fields don't trigger recreation
- View creation happens in a single transaction

**Manual rebuild:**
```bash
php artisan flexyfield:rebuild-view
```

**When to rebuild:**
- After DB restore/migration
- After manual ff_field_values changes
- During deployment verification

## Monitoring

### Health Check
You can monitor view recreation frequency by checking your logs or database query history.
To see total fields in the view:
```php
// Get field count from view metadata
$columns = Schema::getColumnListing('ff_values_pivot_view');
$fieldCount = count($columns) - 2; // Subtract model_type and model_id
```
```php
Route::get('/health/flexyfield', fn() => response()->json([
    'total_fields' => (Schema::hasTable('ff_values_pivot_view') ? count(Schema::getColumnListing('ff_values_pivot_view')) - 2 : 0),
    'total_values' => DB::table('ff_field_values')->count(),
    'view_exists' => !empty(DB::select("SHOW TABLES LIKE 'ff_values_pivot_view'")),
]));
```

### Log Slow Queries

```php
// AppServiceProvider::boot()
DB::listen(fn($q) => str_contains($q->sql, 'ff_field_values') && $q->time > 50 
    && Log::warning('Slow FlexyField query', ['sql' => $q->sql, 'time' => $q->time]));
```

## Scaling

### Vertical Scaling

**Database:** 4+ CPU cores, 16GB+ RAM, SSD storage

**MySQL Config:**
```ini
innodb_buffer_pool_size = 8G
innodb_log_file_size = 512M
```

### Horizontal Scaling

**Read Replicas:**
```php
// config/database.php
'mysql' => [
    'read' => ['host' => ['replica1', 'replica2']],
    'write' => ['host' => ['master']],
],
```

**Caching:**
```php
$product = Cache::remember("product_{$id}", 3600, 
    fn() => Product::find($id)
);
```

## When NOT to Use FlexyField

❌ **Avoid for:**
- Frequently changing schema (100+ changes/day)
- Complex aggregations (`SUM(flexy_price * flexy_quantity)`)
- Critical high-frequency paths (checkout, payments)
- Large text fields (use TEXT column instead)
- Strict ACID requirements with foreign keys

✅ **Ideal for:**
- Product catalogs with varying attributes
- Multi-tenant custom fields
- CMS with flexible content
- Dynamic user profiles
- CRM with customizable entities

## Performance Targets

**Medium Scale (100K models, 50 fields):**
```
Single save:         < 20ms (p95)
Query with filter:   < 50ms (p95)
View recreation:     < 10s
Bulk 1000 updates:   < 30s
```

**Large Scale (1M models, 100 fields):**
```
Single save:         < 50ms (p95)
Query with filter:   < 100ms (p95)
View recreation:     < 60s
Bulk 1000 updates:   < 60s
```

## Maintenance

**Daily:** Monitor view recreation frequency, check slow queries

**Weekly:** Review table growth, analyze index usage

**Monthly:** `OPTIMIZE TABLE ff_field_values`, benchmark performance
