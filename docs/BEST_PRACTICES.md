# Best Practices

Essential patterns for using FlexyField effectively.

## Schema Definition

**Define schemas in seeders** for version control:

```php
// database/seeders/ProductSchemaSeeder.php
$schema = FieldSchema::firstOrCreate([
    'model_type' => Product::class,
    'schema_code' => 'default',
], [
    'label' => 'Default',
    'is_default' => true,
]);

$schema->fields()->firstOrCreate(['name' => 'color'], [
    'type' => FlexyFieldType::STRING->value,
    'validation_rules' => 'required|string|max:50',
    'metadata' => ['label' => 'Color'],
]);
```

**Document in PHPDoc:**

```php
/**
 * @property string $color Product color (required)
 * @property float $weight_kg Weight in kg (optional)
 */
class Product extends Model
{
    use Flexy;
}
```

## Field Naming

- **snake_case**: `shipping_weight_kg`, `is_featured`
- **Include units**: `weight_kg`, `height_cm`, `price_usd`
- **Group related**: `ship_weight_kg`, `ship_fragile`, `ship_method`
- **Boolean prefixes**: `is_active`, `has_warranty`, `can_preorder`

## Validation

**Layer validation:**

```php
// 1. Schema-level (FlexyField enforces)
'validation_rules' => 'required|numeric|min:0|max:999999'

// 2. Model-level (business logic)
protected static function booted() {
    static::saving(function ($model) {
        if ($model->flexy->price > 10000 && !$model->flexy->has_cert) {
            throw new \Exception('Certification required');
        }
    });
}

// 3. Form Request (HTTP layer)
public function rules() {
    return ['flexy.price' => 'required|numeric|min:0'];
}
```

## Data Migration

**Add fields** (no migration needed):

```php
$schema->fields()->firstOrCreate(['name' => 'eco_friendly'], [
    'type' => FlexyFieldType::BOOLEAN->value,
    'validation_rules' => 'nullable|boolean',
]);

// Optional backfill
Product::chunk(100, fn($products) => 
    $products->each(fn($p) => $p->update(['flexy.eco_friendly' => false]))
);
```

**Rename fields:**

1. Add new field
2. Copy data: `INSERT INTO ff_field_values ... SELECT ... WHERE name = 'old_name'`
3. Update code
4. Delete old field

## Testing

**Factory pattern:**

```php
class ProductFactory extends Factory
{
    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            $product->flexy->color = $this->faker->randomElement(['Red', 'Blue']);
            $product->flexy->size = $this->faker->randomElement(['S', 'M', 'L']);
            $product->save();
        });
    }
}
```

**Test flexy fields:**

```php
public function test_validates_required_fields()
{
    $product = Product::factory()->create();
    $product->assignToSchema('default');
    
    $this->expectException(ValidationException::class);
    $product->flexy->size = ''; // required
    $product->save();
}
```

## Common Pitfalls

❌ **Setting before save:**
```php
$product = new Product();
$product->flexy->color = 'Red'; // ERROR: No ID yet!
```

✅ **Save first:**
```php
$product = Product::create(['name' => 'Test']);
$product->flexy->color = 'Red'; // OK
$product->save();
```

❌ **Type confusion:**
```php
$product->flexy->is_active = '0'; // String!
$product->flexy->is_active = 0;   // Integer!
```

✅ **Consistent types:**
```php
$product->flexy->is_act ive = false; // Boolean
$product->flexy->quantity = 0;      // Integer
```

❌ **Missing schema:**
```php
$product->flexy->typo_field = 'value'; // Silently accepted!
```

✅ **Assign schema:**
```php
$product->assignToSchema('default'); // Enforce defined fields
```

## Quick Reference

```php
// CRUD
$product = Product::create(['name' => 'T-Shirt']);
$product->flexy->color = 'Red';
$product->save();

$product = Product::find($id);
echo $product->flexy->color;

$product->flexy->color = 'Blue';
$product->save();

$product->delete(); // Values auto-deleted

// Query
Product::where('flexy_color', 'Red')->get();
Product::where('flexy_price', '>=', 100)->get();
```
