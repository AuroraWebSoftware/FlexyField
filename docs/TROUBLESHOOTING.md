# Troubleshooting Guide

This guide helps diagnose and resolve common issues with FlexyField in production environments.

## Table of Contents

- [Quick Diagnostics](#quick-diagnostics)
- [View Recreation Errors](#view-recreation-errors)
- [Type Validation Failures](#type-validation-failures)
- [Query Performance Issues](#query-performance-issues)
- [JSON Decoding Errors](#json-decoding-errors)
- [Migration Issues](#migration-issues)
- [Data Integrity Issues](#data-integrity-issues)
- [Memory and Resource Issues](#memory-and-resource-issues)
- [Debug Mode](#debug-mode)
- [FAQ](#faq)

## Quick Diagnostics

### Check FlexyField Installation

```bash
# Verify tables exist
php artisan tinker --execute="
    echo 'ff_values: ' . DB::table('ff_values')->count() . PHP_EOL;
    echo 'ff_shapes: ' . DB::table('ff_shapes')->count() . PHP_EOL;
    echo 'ff_view_schema: ' . DB::table('ff_view_schema')->count() . PHP_EOL;
"

# Check view exists
php artisan tinker --execute="
    \$view = DB::select(\"SHOW TABLES LIKE 'ff_values_pivot_view'\");
    echo !empty(\$view) ? 'View exists' : 'View missing';
"
```

### Check Model Configuration

```php
// In tinker
php artisan tinker

// Verify trait is applied
$model = App\Models\Product::first();
$traits = class_uses($model);
// Should include: AuroraWebSoftware\FlexyField\Traits\Flexy

// Test flexy access
$model->flexy->test_field = 'test';
$model->save();
echo $model->fresh()->flexy->test_field; // Should output: test
```

### Check Database Connectivity

```bash
php artisan tinker --execute="
    try {
        DB::connection()->getPdo();
        echo 'Database connected';
    } catch (Exception \$e) {
        echo 'Database error: ' . \$e->getMessage();
    }
"
```

## View Recreation Errors

### Error: "Table 'ff_values_pivot_view' doesn't exist"

**Cause:** View was never created or was dropped.

**Solution:**
```bash
# Rebuild the view
php artisan flexyfield:rebuild-view

# Verify creation
php artisan tinker --execute="
    \$result = DB::select(\"SHOW TABLES LIKE 'ff_values_pivot_view'\");
    echo !empty(\$result) ? 'View created successfully' : 'View creation failed';
"
```

### Error: "Syntax error near 'NULL' in view creation"

**Cause:** Trying to create view when no fields exist yet (empty ff_values table).

**Solution:**
This should be handled automatically in v2.0+. If you encounter this:

```php
// Check if ff_values is empty
php artisan tinker --execute="echo DB::table('ff_values')->count();"

// If empty, this is expected on fresh install
// View will be created automatically when first field is added
```

**Upgrade fix (if on older version):**
Update to FlexyField v2.0+ which handles empty tables correctly.

### Error: "View recreation timeout"

**Cause:** Too many fields or rows causing slow view creation.

**Solution:**
```bash
# Increase PHP timeout
php -d max_execution_time=600 artisan flexyfield:rebuild-view

# For very large databases, schedule during maintenance window
php artisan down
php -d max_execution_time=600 artisan flexyfield:rebuild-view
php artisan up
```

**Prevention:**
Monitor field count and consider denormalization if exceeding 100 fields.

### Error: "Lost connection to MySQL server during query"

**Cause:** View creation exceeding MySQL timeout settings.

**Solution:**
```sql
-- Increase MySQL timeouts temporarily
SET SESSION wait_timeout = 600;
SET SESSION interactive_timeout = 600;

-- Or in /etc/mysql/my.cnf
[mysqld]
wait_timeout = 600
interactive_timeout = 600
max_execution_time = 600000
```

Then retry:
```bash
php artisan flexyfield:rebuild-view
```

## Type Validation Failures

### Error: "FlexyFieldTypeNotAllowedException"

**Cause:** Trying to assign unsupported type or NULL value.

**Example:**
```php
$model->flexy->field = null; // ❌ Not allowed
```

**Solution:**
```php
// Option 1: Use empty string for clearing
$model->flexy->field = '';
$model->save();

// Option 2: Unset the field
unset($model->flexy->field);
$model->save();

// Option 3: Delete the value from database
DB::table('ff_values')
    ->where('model_type', get_class($model))
    ->where('model_id', $model->id)
    ->where('field_name', 'field')
    ->delete();
```

**Allowed types:**
- `string`
- `int`
- `float`/`double`
- `bool`
- `DateTime`/`Carbon`
- `array` (stored as JSON)
- `object` (stored as JSON)

### Error: "Undefined property: stdClass::$field_name"

**Cause:** Trying to access a field that doesn't exist.

**Solution:**
```php
// ❌ Don't do this
echo $model->flexy->nonexistent_field; // Error!

// ✅ Check if field exists first
if (isset($model->flexy->field_name)) {
    echo $model->flexy->field_name;
}

// ✅ Or use null coalescing
echo $model->flexy->field_name ?? 'default value';
```

### Warning: "A non-numeric value encountered"

**Cause:** Type mismatch between stored and expected value.

**Example:**
```php
// Stored as string "abc"
$model->flexy->count = 'abc';
$model->save();

// Later, trying to use as number
$total = $model->flexy->count + 10; // Warning!
```

**Solution:**
```php
// Enforce type validation in Shape
use AuroraWebSoftware\FlexyField\Shapes\Shape;

Shape::define('product', [
    'count' => ['type' => 'integer', 'min' => 0],
]);

// Or validate before using
$count = filter_var($model->flexy->count, FILTER_VALIDATE_INT);
if ($count !== false) {
    $total = $count + 10;
}
```

## Query Performance Issues

### Issue: Slow queries on flexy fields

**Symptoms:**
- Queries taking > 1 second
- Timeout errors
- High database CPU

**Diagnosis:**
```bash
# Enable slow query log
php artisan tinker --execute="
    DB::enableQueryLog();
    App\Models\Product::where('flexy_color', 'red')->get();
    dd(DB::getQueryLog());
"
```

**Solutions:**

**1. Add indexes:**
```php
// Create migration
Schema::table('ff_values', function (Blueprint $table) {
    $table->index('value_string');
    $table->index(['field_name', 'value_string']);
});
```

**2. Use proper query patterns:**
```php
// ❌ Slow: Full table scan
Product::all()->filter(fn($p) => $p->flexy->color == 'red');

// ✅ Fast: Database-level filtering
Product::where('flexy_color', 'red')->get();
```

**3. Limit selected fields:**
```php
// ❌ Loads all flexy fields
Product::select('id', 'name')->get();

// ✅ Only loads specific flexy fields
Product::select('id', 'name', 'flexy_color', 'flexy_size')->get();
```

### Issue: N+1 query problem

**Symptoms:**
- Hundreds of queries for flexy fields
- Linear increase in queries with model count

**Diagnosis:**
```php
DB::enableQueryLog();
$products = Product::all();
foreach ($products as $product) {
    echo $product->flexy->color; // N+1 here?
}
dd(count(DB::getQueryLog()));
```

**Solution:**
FlexyField automatically eager loads via pivot view, but verify:

```php
// Check that view join is working
$product = Product::first();
$sql = $product->toSql();
// Should include: LEFT JOIN ff_values_pivot_view
```

If N+1 still occurs, rebuild view:
```bash
php artisan flexyfield:rebuild-view
```

### Issue: "Too many columns in join"

**Cause:** Pivot view has > 4096 columns (MySQL limit).

**Solution:**
You have too many fields. Consider:
1. Moving some fields to regular columns
2. Splitting into multiple model types
3. Using document database for this use case

## JSON Decoding Errors

### Error: "Syntax error" when accessing JSON field

**Cause:** Invalid JSON stored in value_json column.

**Diagnosis:**
```php
php artisan tinker --execute="
    \$value = DB::table('ff_values')
        ->where('field_name', 'problematic_field')
        ->first();
    echo \$value->value_json;
    // Check if valid JSON
    json_decode(\$value->value_json);
    echo json_last_error_msg();
"
```

**Solution:**
```php
// Fix invalid JSON in database
DB::table('ff_values')
    ->where('field_name', 'problematic_field')
    ->update([
        'value_json' => json_encode(['default' => 'value']),
    ]);

// Or delete invalid entries
DB::table('ff_values')
    ->where('field_name', 'problematic_field')
    ->where('value_json', 'NOT LIKE', '%{%')
    ->delete();
```

**Prevention:**
```php
// Always validate before saving
$data = ['key' => 'value'];
$model->flexy->settings = $data; // Will be JSON encoded automatically
$model->save();
```

## Migration Issues

### Error: "Table already exists"

**Cause:** Migrations already run or partially run.

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# If showing as not run but tables exist
# Mark as run without executing
php artisan migrate --pretend

# Or reset and re-run (⚠️ DESTRUCTIVE)
php artisan migrate:fresh
```

### Error: "Unknown column in field list"

**Cause:** Migration order issue or missing migration.

**Solution:**
```bash
# Check which migrations have run
php artisan migrate:status

# Run missing migrations
php artisan migrate --path=vendor/aurorawebsoftware/flexyfield/database/migrations

# Check column exists
php artisan tinker --execute="
    \$columns = Schema::getColumnListing('ff_values');
    dd(\$columns);
"
```

### Issue: ff_view_schema table missing

**Cause:** Running old migration from FlexyField v1.x.

**Solution:**
Upgrade to v2.0+ or manually create table:

```php
// Create migration
Schema::create('ff_view_schema', function (Blueprint $table) {
    $table->id();
    $table->string('field_name')->unique();
    $table->timestamp('added_at')->useCurrent();
});

// Populate from existing data
$fields = DB::table('ff_values')
    ->select('field_name')
    ->distinct()
    ->get();

foreach ($fields as $field) {
    DB::table('ff_view_schema')->insert([
        'field_name' => $field->field_name,
        'added_at' => now(),
    ]);
}
```

## Data Integrity Issues

### Issue: Fields missing after view rebuild

**Cause:** Data in ff_values but not in view.

**Diagnosis:**
```php
// Check ff_values
$valuesFields = DB::table('ff_values')
    ->select('field_name')
    ->distinct()
    ->pluck('field_name');

// Check ff_view_schema
$schemaFields = DB::table('ff_view_schema')
    ->pluck('field_name');

// Find missing
$missing = $valuesFields->diff($schemaFields);
dd($missing);
```

**Solution:**
```bash
# Force full rebuild
php artisan flexyfield:rebuild-view

# Verify
php artisan tinker --execute="
    \$fields = DB::table('ff_view_schema')->pluck('field_name');
    dd(\$fields);
"
```

### Issue: Duplicate field names

**Cause:** Direct database manipulation bypassing uniqueness constraint.

**Diagnosis:**
```php
$duplicates = DB::table('ff_values')
    ->select('model_type', 'model_id', 'field_name', DB::raw('COUNT(*) as count'))
    ->groupBy('model_type', 'model_id', 'field_name')
    ->having('count', '>', 1)
    ->get();
```

**Solution:**
```php
// Remove duplicates, keeping the most recent
foreach ($duplicates as $dup) {
    $records = DB::table('ff_values')
        ->where('model_type', $dup->model_type)
        ->where('model_id', $dup->model_id)
        ->where('field_name', $dup->field_name)
        ->orderBy('id', 'desc')
        ->get();

    // Keep first, delete rest
    $records->shift();
    foreach ($records as $record) {
        DB::table('ff_values')->where('id', $record->id)->delete();
    }
}
```

### Issue: Orphaned values

**Cause:** Model deleted but flexy values remain.

**Diagnosis:**
```php
// Find orphaned values (models that don't exist)
$orphaned = DB::table('ff_values')
    ->select('model_type', 'model_id')
    ->distinct()
    ->get()
    ->filter(function ($value) {
        $modelClass = $value->model_type;
        return !$modelClass::find($value->model_id);
    });
```

**Solution:**
```php
// Clean up orphaned values
foreach ($orphaned as $orphan) {
    DB::table('ff_values')
        ->where('model_type', $orphan->model_type)
        ->where('model_id', $orphan->model_id)
        ->delete();
}

// Or use cascade delete in model
class Product extends Model
{
    use Flexy;

    protected static function booted()
    {
        static::deleting(function ($model) {
            DB::table('ff_values')
                ->where('model_type', get_class($model))
                ->where('model_id', $model->id)
                ->delete();
        });
    }
}
```

## Memory and Resource Issues

### Error: "Allowed memory size exhausted"

**Cause:** Loading too many models or large JSON fields.

**Solution:**
```php
// ❌ Don't load all at once
$products = Product::all(); // Memory exhausted!

// ✅ Use chunking
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process
    }
});

// ✅ Or cursor for very large datasets
foreach (Product::cursor() as $product) {
    // Process one at a time
}
```

**Increase memory (temporary fix):**
```bash
php -d memory_limit=512M artisan your:command
```

### Issue: Slow performance on large datasets

**Cause:** Not using pagination or chunking.

**Solution:**
```php
// ❌ Slow
$products = Product::where('flexy_status', 'active')->get();

// ✅ Fast
$products = Product::where('flexy_status', 'active')->paginate(50);

// ✅ For API
$products = Product::where('flexy_status', 'active')->simplePaginate(50);
```

## Debug Mode

### Enable Query Logging

```php
// In AppServiceProvider.php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

public function boot()
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            Log::debug('Query executed', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        });
    }
}
```

### Enable FlexyField Debug

```php
// Create config/flexyfield.php
return [
    'debug' => env('FLEXYFIELD_DEBUG', false),
];

// In .env
FLEXYFIELD_DEBUG=true

// In FlexyField operations
if (config('flexyfield.debug')) {
    Log::debug('FlexyField operation', [
        'model' => get_class($model),
        'fields' => $dirtyFields,
        'recreated' => $wasRecreated,
    ]);
}
```

### Dump View Structure

```php
php artisan tinker --execute="
    // View columns
    \$columns = DB::select('DESCRIBE ff_values_pivot_view');
    foreach (\$columns as \$col) {
        echo \$col->Field . ' (' . \$col->Type . ')' . PHP_EOL;
    }

    // Sample data
    \$sample = DB::table('ff_values_pivot_view')->first();
    dd(\$sample);
"
```

## FAQ

### Q: Can I rename a flexy field?

**A:** Yes, but requires manual update:

```php
DB::table('ff_values')
    ->where('field_name', 'old_name')
    ->update(['field_name' => 'new_name']);

// Update shape definition
DB::table('ff_shapes')
    ->where('field_name', 'old_name')
    ->update(['field_name' => 'new_name']);

// Rebuild view
Artisan::call('flexyfield:rebuild-view');
```

### Q: Can I change a field's type?

**A:** Yes, but requires data migration:

```php
// Example: string to int
$values = DB::table('ff_values')
    ->where('field_name', 'quantity')
    ->whereNotNull('value_string')
    ->get();

foreach ($values as $value) {
    $intValue = (int) $value->value_string;
    DB::table('ff_values')
        ->where('id', $value->id)
        ->update([
            'value_string' => null,
            'value_int' => $intValue,
        ]);
}

// Update shape
Shape::define('product', [
    'quantity' => ['type' => 'integer'],
]);
```

### Q: How do I backup only FlexyField data?

**A:**
```bash
mysqldump -u user -p database_name ff_values ff_shapes ff_view_schema > flexyfield_backup.sql
```

### Q: Can I use FlexyField with multi-tenancy?

**A:** Yes, but consider:
- Separate ff_values per tenant (recommended)
- Or use tenant_id in ff_values (add index)
- View recreation per tenant

### Q: What's the maximum number of fields?

**A:** Technical limit: ~4000 (MySQL column limit)
Practical limit: ~200 fields per model type
Recommended: < 50 fields per model type

### Q: Why are my queries slow?

**A:** Common causes:
1. Missing indexes on ff_values
2. Too many fields (> 100)
3. Large JSON fields
4. Not using proper query patterns
5. Need to rebuild view

See [Query Performance Issues](#query-performance-issues) section.

### Q: Can I use FlexyField with MongoDB?

**A:** No, FlexyField requires relational database (MySQL/PostgreSQL).
For document databases, use native flexible schemas.

### Q: How do I completely uninstall FlexyField?

**A:**
```bash
# Drop tables
php artisan tinker --execute="
    Schema::dropIfExists('ff_values_pivot_view');
    Schema::dropIfExists('ff_values');
    Schema::dropIfExists('ff_shapes');
    Schema::dropIfExists('ff_view_schema');
"

# Remove package
composer remove aurorawebsoftware/flexyfield

# Remove trait from models
# Manually edit model files to remove 'use Flexy;'
```

## Getting Help

If you can't resolve your issue:

1. **Check Documentation:**
   - README.md
   - PERFORMANCE.md
   - BEST_PRACTICES.md
   - DEPLOYMENT.md

2. **Enable Debug Mode:**
   - Set `APP_DEBUG=true`
   - Check `storage/logs/laravel.log`

3. **Gather Information:**
   - PHP version: `php -v`
   - Laravel version: `php artisan --version`
   - FlexyField version: `composer show aurorawebsoftware/flexyfield`
   - Database version: `SELECT VERSION();`
   - Error message and stack trace
   - Steps to reproduce

4. **Report Issue:**
   - GitHub Issues: https://github.com/aurorawebsoftware/flexyfield/issues
   - Include all gathered information
   - Provide minimal reproducible example

5. **Community:**
   - Laravel Forums
   - Stack Overflow (tag: laravel, flexyfield)
   - Laravel Discord
