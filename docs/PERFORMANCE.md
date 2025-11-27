# Performance Guide

This guide covers performance characteristics, optimization strategies, and best practices for running FlexyField in production environments.

## Table of Contents

- [Performance Characteristics](#performance-characteristics)
- [Field Count Recommendations](#field-count-recommendations)
- [Query Optimization](#query-optimization)
- [Indexing Strategy](#indexing-strategy)
- [View Recreation Optimization](#view-recreation-optimization)
- [Monitoring & Metrics](#monitoring--metrics)
- [Scaling Strategies](#scaling-strategies)
- [When NOT to Use FlexyField](#when-not-to-use-flexyfield)
- [Production Checklist](#production-checklist)

## Performance Characteristics

### Read Performance

**Query Performance:**
- Direct field queries via pivot view: **Near-native performance** (within 10-20% of standard columns)
- Complex queries with multiple flexy fields: **Good** (20-40% slower than standard joins)
- Full table scans: **Similar to standard EAV** (index-dependent)

**Typical Response Times (1M rows):**
```
Single model load with 5 flexy fields:     ~15-25ms
WHERE query on indexed flexy field:        ~30-50ms
Join queries with flexy field conditions:  ~40-80ms
Eager loading 100 models (10 fields each): ~150-300ms
```

### Write Performance

**Before Optimization (v1.0):**
- Single save: 50-100ms (includes view recreation)
- Bulk 100 updates: 50-100 seconds (100 view recreations)

**After Optimization (v2.0+):**
- Single save (existing field): 5-15ms (no view recreation)
- Single save (new field): 50-100ms (one view recreation)
- Bulk 100 updates (same fields): 2-5 seconds (1 view recreation)
- Bulk 100 updates (new fields): 5-10 seconds (few recreations)

**Performance Improvement: ~98% for typical workloads**

## Field Count Recommendations

### Small Scale (✅ Recommended)
- **Fields per model type:** 1-20
- **Total flexy fields system-wide:** Up to 50
- **Models with flexy data:** Up to 100K
- **Performance:** Excellent, near-native
- **Use cases:** Product variants, user profiles, simple CMS

### Medium Scale (✅ Supported)
- **Fields per model type:** 20-50
- **Total flexy fields system-wide:** 50-200
- **Models with flexy data:** 100K-1M
- **Performance:** Good, minimal overhead
- **Use cases:** E-commerce catalogs, CRM systems, PIM systems
- **Considerations:** Monitor view recreation times, ensure proper indexing

### Large Scale (⚠️ Evaluate Carefully)
- **Fields per model type:** 50-100
- **Total flexy fields system-wide:** 200-500
- **Models with flexy data:** 1M-10M
- **Performance:** Acceptable with optimization
- **Use cases:** Enterprise PIM, multi-tenant SaaS
- **Requirements:**
  - Dedicated database server
  - Regular view recreation monitoring
  - Query optimization required
  - Consider read replicas

### Very Large Scale (❌ Not Recommended)
- **Fields per model type:** 100+
- **Total flexy fields system-wide:** 500+
- **Models with flexy data:** 10M+
- **Recommendation:** Consider alternative architectures
- **Alternatives:**
  - Document databases (MongoDB, Elasticsearch)
  - Dedicated column-store databases
  - Hybrid approach (FlexyField + dedicated tables)

## Query Optimization

### Use Indexes on Frequently Queried Fields

FlexyField automatically indexes `model_type`, `model_id`, and `field_name`, but you may need additional indexes:

```php
// For queries that filter by specific flexy field values
Schema::table('ff_values', function (Blueprint $table) {
    // If you frequently query by value_string
    $table->index('value_string');

    // Composite index for common query patterns
    $table->index(['field_name', 'value_string']);

    // For datetime range queries
    $table->index('value_datetime');
});
```

### Eager Loading

Always eager load flexy fields when loading multiple models:

```php
// ❌ Bad: N+1 queries
$products = Product::all();
foreach ($products as $product) {
    echo $product->flexy->color; // Separate query for each!
}

// ✅ Good: Pre-loaded via pivot view
$products = Product::all(); // Flexy fields loaded automatically via global scope
foreach ($products as $product) {
    echo $product->flexy->color; // No additional queries
}
```

### Query Specific Fields

When you only need specific flexy fields, select them explicitly:

```php
// ❌ Loads all flexy fields
$products = Product::all();

// ✅ Only loads specific fields
$products = Product::select('id', 'name', 'flexy_color', 'flexy_size')->get();
```

### Use Database Views Effectively

The `ff_values_pivot_view` is your friend for complex queries:

```php
// Query directly against the view for complex conditions
$results = DB::table('ff_values_pivot_view')
    ->where('model_type', Product::class)
    ->where('flexy_color', 'red')
    ->where('flexy_size', 'L')
    ->get();
```

### Avoid Wildcards in LIKE Queries

```php
// ❌ Slow: Leading wildcard
Product::where('flexy_name', 'like', '%product%')->get();

// ✅ Better: Trailing wildcard (can use index)
Product::where('flexy_name', 'like', 'product%')->get();

// ✅ Best: Full-text search for complex search needs
// Consider adding full-text index or use Elasticsearch
```

## Indexing Strategy

### Default Indexes (Automatic)

FlexyField creates these indexes automatically:

```sql
ff_shapes:
- INDEX (model_type)
- INDEX (field_name)
- UNIQUE (model_type, field_name)

ff_values:
- INDEX (model_type)
- INDEX (model_id)
- INDEX (field_name)
- UNIQUE (model_type, model_id, field_name)

ff_view_schema:
- UNIQUE (field_name)
```

### Recommended Additional Indexes

Based on your query patterns:

```php
// For filtering by specific value types
Schema::table('ff_values', function (Blueprint $table) {
    // String value searches
    $table->index('value_string');

    // Numeric comparisons
    $table->index('value_int');
    $table->index('value_decimal');

    // Date range queries
    $table->index('value_date');
    $table->index('value_datetime');
});

// Composite indexes for common patterns
Schema::table('ff_values', function (Blueprint $table) {
    // For field-specific value lookups
    $table->index(['field_name', 'value_string'], 'idx_field_string');
    $table->index(['field_name', 'value_int'], 'idx_field_int');

    // For model-specific field access
    $table->index(['model_type', 'model_id', 'field_name'], 'idx_model_field');
});
```

### Index Monitoring

Monitor index usage with:

```sql
-- MySQL
SHOW INDEX FROM ff_values;
SELECT * FROM information_schema.STATISTICS
WHERE table_name = 'ff_values';

-- Check unused indexes
SELECT * FROM sys.schema_unused_indexes
WHERE object_schema = 'your_database';
```

## View Recreation Optimization

### How It Works (v2.0+)

FlexyField now uses smart change detection:

1. **Schema tracking:** `ff_view_schema` table tracks known fields
2. **Change detection:** Only recreates view when NEW fields are added
3. **Bulk efficiency:** 1000 updates = 1-2 recreations (not 1000!)

### Manual Rebuild

Force a full view rebuild when needed:

```bash
php artisan flexyfield:rebuild-view
```

When to rebuild:
- After database restore/migration
- If view and data are out of sync
- After manual ff_values table changes
- During deployment verification

### Monitoring View Recreation

Track view recreation frequency:

```php
// Add monitoring in your AppServiceProvider
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

DB::listen(function ($query) {
    if (str_contains($query->sql, 'ff_values_pivot_view')) {
        Log::channel('performance')->info('View operation', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

### Batch Operations

For large imports/updates, minimize view recreation:

```php
// Process in batches with same field set
DB::transaction(function () {
    foreach ($products as $product) {
        $product->flexy->color = 'red';
        $product->flexy->size = 'L';
        $product->save(); // View recreated only once for 'color' and 'size'
    }
});
```

## Monitoring & Metrics

### Key Metrics to Track

**Database Metrics:**
```
- ff_values table size
- ff_values row count
- View recreation frequency
- Average query time on ff_values_pivot_view
- Index hit rate on ff_values
```

**Application Metrics:**
```
- Average save time for models with flexy fields
- 95th percentile query time
- View recreation duration
- Failed validation rate
```

### Laravel Telescope Integration

```php
// Monitor slow queries
Telescope::filter(function (IncomingEntry $entry) {
    if ($entry->type === 'query') {
        return $entry->content['time'] > 50 ||
               str_contains($entry->content['sql'], 'ff_values');
    }
    return false;
});
```

### Health Check Endpoint

```php
// routes/web.php
Route::get('/health/flexyfield', function () {
    $stats = [
        'total_fields' => DB::table('ff_view_schema')->count(),
        'total_values' => DB::table('ff_values')->count(),
        'view_exists' => DB::select("SHOW TABLES LIKE 'ff_values_pivot_view'"),
        'last_recreation' => DB::table('ff_view_schema')
            ->max('added_at'),
    ];

    return response()->json($stats);
});
```

## Scaling Strategies

### Vertical Scaling

**Database Server:**
- CPU: 4+ cores for view recreation operations
- RAM: 16GB+ for large pivot views
- Storage: SSD required for decent performance

**Optimization Settings (MySQL):**
```ini
innodb_buffer_pool_size = 8G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 128M  # If using MySQL 5.7
```

### Horizontal Scaling

**Read Replicas:**
```php
// config/database.php
'connections' => [
    'mysql' => [
        'read' => [
            'host' => ['192.168.1.2', '192.168.1.3'],
        ],
        'write' => [
            'host' => ['192.168.1.1'],
        ],
        // ... other config
    ],
],
```

**Caching Strategy:**
```php
// Cache frequently accessed flexy values
use Illuminate\Support\Facades\Cache;

$product = Cache::remember("product_{$id}_with_flexy", 3600, function () use ($id) {
    return Product::find($id);
});
```

### Partitioning Strategy

For very large datasets:

```sql
-- Partition ff_values by model_type
ALTER TABLE ff_values
PARTITION BY HASH(CRC32(model_type))
PARTITIONS 8;
```

## When NOT to Use FlexyField

### ❌ Anti-Patterns

**1. Frequently Changing Schema (100+ changes/day)**
- Every new field triggers view recreation
- Consider document database instead

**2. Complex Multi-Field Aggregations**
```php
// ❌ Don't do this
SELECT
    SUM(flexy_price * flexy_quantity) as total,
    AVG(flexy_rating)
FROM products;
// Use standard columns for aggregated fields
```

**3. Critical High-Frequency Paths**
- Checkout flow, payment processing
- Real-time bidding, stock trading
- Use standard columns for critical paths

**4. Strict ACID Requirements**
- Complex multi-field transactions
- Banking, financial systems
- Foreign key constraints across flexy fields

**5. Very Large Text Fields**
```php
// ❌ Don't store large text in flexy fields
$model->flexy->blog_content = $largeHtmlContent; // Bad!

// ✅ Use TEXT column or separate table
Schema::table('products', function ($table) {
    $table->text('description');
});
```

### ✅ Ideal Use Cases

- Product catalogs with varying attributes
- Multi-tenant systems with custom fields per tenant
- CMS with flexible content types
- User profiles with dynamic properties
- CRM with customizable entity fields
- Form builders with dynamic schemas

## Production Checklist

### Before Deployment

- [ ] Performance tested with production-like data volume
- [ ] Indexes added for common query patterns
- [ ] Monitoring setup for view recreation frequency
- [ ] Database backup strategy in place
- [ ] View recreation tested (time < 10 seconds)
- [ ] Rollback procedure documented
- [ ] Load testing completed (expected peak traffic)
- [ ] Query slow log analyzed
- [ ] ff_values table size calculated and accepted
- [ ] Read replicas configured (if needed)

### After Deployment

- [ ] Verify view exists: `SHOW TABLES LIKE 'ff_values_pivot_view'`
- [ ] Check initial view recreation time
- [ ] Monitor query performance for 24 hours
- [ ] Verify validation errors are handled correctly
- [ ] Check ff_view_schema tracking is working
- [ ] Review slow query log
- [ ] Confirm backup is working
- [ ] Test health check endpoint

### Regular Maintenance

**Daily:**
- Monitor view recreation frequency
- Check slow query log

**Weekly:**
- Review ff_values table growth
- Analyze index usage
- Check for missing indexes

**Monthly:**
- Optimize tables: `OPTIMIZE TABLE ff_values`
- Review and update indexes
- Performance benchmarking
- Capacity planning review

### Performance Targets

**For Medium Scale (100K models, 50 fields):**
```
Single model save:           < 20ms (95th percentile)
Query with flexy filter:     < 50ms (95th percentile)
View recreation:             < 10 seconds
Bulk 1000 updates:          < 30 seconds
```

**For Large Scale (1M models, 100 fields):**
```
Single model save:           < 50ms (95th percentile)
Query with flexy filter:     < 100ms (95th percentile)
View recreation:             < 60 seconds
Bulk 1000 updates:          < 60 seconds
```

If your metrics exceed these targets, review indexing and consider scaling strategies.
