# Troubleshooting Guide

Common issues and quick solutions.

## Quick Diagnostics

```bash
# Verify installation
php artisan tinker --execute="echo DB::table('ff_field_values')->count();"
php artisan tinker --execute="dd(DB::select(\"SHOW TABLES LIKE 'ff_values_pivot_view'\"));"

# Test flexy access
$model = App\Models\Product::first();
$model->flexy->test = 'value';
$model->save();
echo $model->fresh()->flexy->test; // Should output: value
```

## View Issues

**"Table 'ff_values_pivot_view' doesn't exist"**
```bash
php artisan flexyfield:rebuild-view
```

**View recreation timeout**
```bash
php -d max_execution_time=600 artisan flexyfield:rebuild-view
```

**Lost MySQL connection**
```sql
SET SESSION wait_timeout = 600;
SET SESSION interactive_timeout = 600;
```

## Type Validation

**Null values not allowed:**
```php
// ❌ Wrong
$model->flexy->field = null;

// ✅ Correct
$model->flexy->field = '';
unset($model->flexy->field);
```

**Check field exists:**
```php
// ❌ Error if missing
echo $model->flexy->field;

// ✅ Safe
echo $model->flexy->field ?? 'default';
if (isset($model->flexy->field)) { }
```

## Performance Issues

**Slow queries - Add indexes:**
```php
Schema::table('ff_field_values', function (Blueprint $table) {
    $table->index('value_string');
    $table->index(['name', 'value_string']);
});
```

**Use proper query patterns:**
```php
// ❌ Slow
Product::all()->filter(fn($p) => $p->flexy->color == 'red');

// ✅ Fast
Product::where('flexy_color', 'red')->get();
```

**Memory issues - Use chunking:**
```php
// ❌ Memory exhausted
$products = Product::all();

// ✅ Chunk
Product::chunk(100, fn($products) => /* process */);

// ✅ Cursor  
foreach (Product::cursor() as $product) { }
```

## Data Issues

**Fields missing after rebuild:**
```bash
php artisan flexyfield:rebuild-view
```

**Find orphaned values:**
```php
$orphaned = DB::table('ff_field_values')
    ->select('model_type', 'model_id')->distinct()
    ->get()->filter(fn($v) => !$v->model_type::find($v->model_id));

// Clean up
foreach ($orphaned as $o) {
    DB::table('ff_field_values')
        ->where('model_type', $o->model_type)
        ->where('model_id', $o->model_id)
        ->delete();
}
```

**Remove duplicates:**
```php
$duplicates = DB::table('ff_field_values')
    ->select('model_type', 'model_id', 'name', DB::raw('COUNT(*) as count'))
    ->groupBy('model_type', 'model_id', 'name')
    ->having('count', '>', 1)
    ->get();
```

## JSON Errors

**Invalid JSON:**
```php
// Check
$value = DB::table('ff_field_values')->where('name', 'field')->first();
json_decode($value->value_json);
echo json_last_error_msg();

// Fix
DB::table('ff_field_values')
    ->where('name', 'field')
    ->update(['value_json' => json_encode(['default' => 'value'])]);
```

## Migration Issues

**"Table already exists"**
```bash
php artisan migrate:status
php artisan migrate --pretend
```

**Missing ff_view_schema table:**
```php
Schema::create('ff_view_schema', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->timestamp('added_at')->useCurrent();
});
```

## Debug Mode

**Enable query logging:**
```php
// AppServiceProvider::boot()
DB::listen(fn($q) => Log::debug('Query', [
    'sql' => $q->sql,
    'time' => $q->time
]));
```

**Dump view structure:**
```bash
php artisan tinker --execute="dd(DB::select('DESCRIBE ff_values_pivot_view'));"
```

## FAQ

**Rename field:**
```php
DB::table('ff_field_values')->where('name', 'old')->update(['name' => 'new']);
DB::table('ff_schema_fields')->where('name', 'old')->update(['name' => 'new']);
Artisan::call('flexyfield:rebuild-view');
```

**Change field type:**
```php
// Migrate data
$values = DB::table('ff_field_values')->where('name', 'field')->get();
foreach ($values as $v) {
    DB::table('ff_field_values')->where('id', $v->id)->update([
        'value_string' => null,
        'value_int' => (int)$v->value_string,
    ]);
}
// Update schema
DB::table('ff_schema_fields')->where('name', 'field')
    ->update(['type' => FlexyFieldType::INTEGER->value]);
```

**Backup FlexyField data:**
```bash
mysqldump -u user -p db ff_field_values ff_schemas ff_schema_fields ff_view_schema > backup.sql
```

**Limits:**
- Technical: ~4000 fields (MySQL column limit)
- Practical: ~200 fields per model
- Recommended: <50 fields per model

**Uninstall:**
```bash
php artisan tinker --execute="
    Schema::dropIfExists('ff_values_pivot_view');
    Schema::dropIfExists('ff_field_values');
    Schema::dropIfExists('ff_schema_fields');
    Schema::dropIfExists('ff_schemas');
    Schema::dropIfExists('ff_view_schema');
"
composer remove aurorawebsoftware/flexyfield
```

## Getting Help

1. Check logs: `storage/logs/laravel.log`
2. Enable debug: `APP_DEBUG=true`
3. Gather info: PHP/Laravel/DB versions, error trace
4. Report: GitHub Issues with minimal reproducible example
