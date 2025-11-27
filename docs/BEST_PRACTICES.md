# Best Practices

This guide covers recommended patterns, conventions, and practices for using FlexyField effectively in production applications.

## Table of Contents

- [Field Set Definition](#field-set-definition)
- [Field Naming Conventions](#field-naming-conventions)
- [Validation Strategies](#validation-strategies)
- [Data Migration Patterns](#data-migration-patterns)
- [Testing](#testing)
- [Common Pitfalls](#common-pitfalls)
- [Code Examples](#code-examples)

## Field Set Definition

### Define Field Sets in Seeders

Always define field sets in database seeders for version control and consistency:

```php
// database/seeders/ProductFieldSetSeeder.php
namespace Database\Seeders;

use App\Models\Product;
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Models\FieldSet;
use Illuminate\Database\Seeder;

class ProductFieldSetSeeder extends Seeder
{
    public function run(): void
    {
        // Create default field set
        $fieldSet = FieldSet::firstOrCreate([
            'model_type' => Product::class,
            'set_code' => 'default',
        ], [
            'label' => 'Default',
            'description' => 'Default product field set',
            'is_default' => true,
        ]);

        // Define product fields
        $fieldSet->fields()->firstOrCreate(
            ['field_name' => 'color'],
            [
                'field_type' => FlexyFieldType::STRING->value,
                'sort' => 1,
                'validation_rules' => 'required|string|max:50',
                'validation_messages' => ['color.required' => 'Product color is required'],
                'field_metadata' => ['label' => 'Color', 'placeholder' => 'e.g., Red, Blue'],
            ]
        );

        $fieldSet->fields()->firstOrCreate(
            ['field_name' => 'size'],
            [
                'field_type' => FlexyFieldType::STRING->value,
                'sort' => 2,
                'validation_rules' => 'required|in:XS,S,M,L,XL,XXL',
                'validation_messages' => ['size.in' => 'Size must be a valid clothing size'],
                'field_metadata' => ['label' => 'Size', 'type' => 'select'],
            ]
        );

        $fieldSet->fields()->firstOrCreate(
            ['field_name' => 'weight_kg'],
            [
                'field_type' => FlexyFieldType::DECIMAL->value,
                'sort' => 3,
                'validation_rules' => 'nullable|numeric|min:0|max:1000',
                'field_metadata' => ['label' => 'Weight (kg)', 'unit' => 'kg'],
            ]
        );

        $fieldSet->fields()->firstOrCreate(
            ['field_name' => 'in_stock'],
            [
                'field_type' => FlexyFieldType::BOOLEAN->value,
                'sort' => 4,
                'validation_rules' => 'required|boolean',
                'field_metadata' => ['label' => 'In Stock'],
            ]
        );
    }
}
```

### Field Set Versioning

Version your field sets for manageable schema evolution:

```php
// database/seeders/ProductFieldSetV2Seeder.php
class ProductFieldSetV2Seeder extends Seeder
{
    public function run(): void
    {
        $fieldSet = FieldSet::where('model_type', Product::class)
            ->where('set_code', 'default')
            ->first();

        // V2 additions
        $fieldSet->fields()->firstOrCreate(
            ['field_name' => 'brand'],
            [
                'field_type' => FlexyFieldType::STRING->value,
                'sort' => 5,
                'validation_rules' => 'nullable|string|max:100',
                'field_metadata' => ['label' => 'Brand', 'version' => 2],
            ]
        );

        $fieldSet->fields()->firstOrCreate(
            ['field_name' => 'warranty_months'],
            [
                'field_type' => FlexyFieldType::INTEGER->value,
                'sort' => 6,
                'validation_rules' => 'nullable|integer|min:0|max:120',
                'field_metadata' => ['label' => 'Warranty (months)', 'version' => 2],
            ]
        );
    }
}
```

### Field Set Documentation

Document your field sets in code:

```php
/**
 * Product Flexy Fields Schema
 *
 * @property string $color       Product color (required, max 50 chars)
 * @property string $size        Size (XS-XXL, required)
 * @property float  $weight_kg   Weight in kilograms (optional, 0-1000)
 * @property bool   $in_stock    Stock availability (required)
 * @property string $brand       Brand name (optional, v2+)
 * @property int    $warranty_months Warranty period (optional, v2+)
 */
class Product extends Model
{
    use Flexy;

    // ...
}
```

## Field Naming Conventions

### Use Snake Case

Follow Laravel conventions:

```php
// ✅ Good
$product->flexy->shipping_weight_kg = 2.5;
$product->flexy->is_featured = true;
$product->flexy->max_order_quantity = 100;

// ❌ Bad
$product->flexy->shippingWeightKG = 2.5;  // camelCase
$product->flexy->IsFeatured = true;       // PascalCase
$product->flexy->MAX_QTY = 100;           // SCREAMING_SNAKE
```

### Include Unit in Name

For numeric fields with units:

```php
// ✅ Good: Clear units
$product->flexy->weight_kg = 2.5;
$product->flexy->height_cm = 180;
$product->flexy->price_usd = 29.99;
$product->flexy->duration_minutes = 45;

// ❌ Bad: Ambiguous
$product->flexy->weight = 2.5;    // kg? lbs? grams?
$product->flexy->height = 180;    // cm? inches? mm?
$product->flexy->price = 29.99;   // USD? EUR?
```

### Use Prefixes for Related Fields

Group related fields with prefixes:

```php
// Product dimensions
$product->flexy->dim_length_cm = 30;
$product->flexy->dim_width_cm = 20;
$product->flexy->dim_height_cm = 10;

// Shipping information
$product->flexy->ship_weight_kg = 2.5;
$product->flexy->ship_fragile = true;
$product->flexy->ship_method = 'express';

// Meta information
$product->flexy->meta_views = 1250;
$product->flexy->meta_rating = 4.5;
$product->flexy->meta_review_count = 42;
```

### Boolean Field Naming

Use clear boolean prefixes:

```php
// ✅ Good: Clear intent
$product->flexy->is_featured = true;
$product->flexy->has_warranty = true;
$product->flexy->can_pre_order = false;
$product->flexy->requires_assembly = true;

// ❌ Bad: Unclear
$product->flexy->featured = true;      // Could be string "featured"
$product->flexy->warranty = true;      // Could be warranty text
$product->flexy->preorder = false;     // Verb? Noun?
```

## Validation Strategies

### Layered Validation

Implement validation at multiple layers:

```php
// Layer 1: Field Set-level validation (enforced by FlexyField)
$fieldSet = FieldSet::where('model_type', Product::class)
    ->where('set_code', 'default')
    ->first();

$fieldSet->fields()->firstOrCreate(
    ['field_name' => 'price'],
    [
        'field_type' => FlexyFieldType::DECIMAL->value,
        'sort' => 1,
        'validation_rules' => 'required|numeric|min:0|max:999999',
    ]
);

// Layer 2: Model-level validation
class Product extends Model
{
    use Flexy;

    protected static function booted()
    {
        static::saving(function ($product) {
            // Business logic validation
            if ($product->flexy->price > 10000 && !$product->flexy->has_luxury_cert) {
                throw new \Exception('High-value products require luxury certification');
            }
        });
    }
}

// Layer 3: Form Request validation
class UpdateProductRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'flexy.price' => 'required|numeric|min:0',
            'flexy.color' => 'required|string',
            // Additional business rules
        ];
    }
}
```

### Custom Validation Messages

Provide clear, user-friendly messages:

```php
$fieldSet->fields()->firstOrCreate(
    ['field_name' => 'email'],
    [
        'field_type' => FlexyFieldType::STRING->value,
        'sort' => 10,
        'validation_rules' => 'required|email|max:255',
        'validation_messages' => [
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.max' => 'Email address must not exceed 255 characters',
        ],
    ]
);
```

### Conditional Validation

Use closures for complex validation:

```php
class Product extends Model
{
    use Flexy;

    protected static function booted()
    {
        static::saving(function ($product) {
            // If digital product, download_url is required
            if ($product->type === 'digital' && empty($product->flexy->download_url)) {
                throw ValidationException::withMessages([
                    'flexy.download_url' => 'Download URL is required for digital products',
                ]);
            }

            // If physical product, weight is required
            if ($product->type === 'physical' && empty($product->flexy->weight_kg)) {
                throw ValidationException::withMessages([
                    'flexy.weight_kg' => 'Weight is required for physical products',
                ]);
            }
        });
    }
}
```

## Data Migration Patterns

### Adding New Fields

```php
// Migration: No database migration needed for flexy fields!
// Just add to seeder:

// database/seeders/ProductFieldSetSeeder.php
public function run(): void
{
    $fieldSet = FieldSet::where('model_type', Product::class)
        ->where('set_code', 'default')
        ->first();

    // New field (v2)
    $fieldSet->fields()->firstOrCreate(
        ['field_name' => 'eco_friendly'],
        [
            'field_type' => FlexyFieldType::BOOLEAN->value,
            'sort' => 100,
            'validation_rules' => 'nullable|boolean',
            'field_metadata' => ['label' => 'Eco Friendly', 'version' => 2],
        ]
    );
}

// Optional: Backfill existing records
foreach (Product::cursor() as $product) {
    $product->flexy->eco_friendly = false; // Default value
    $product->save();
}
```

### Renaming Fields

```php
// Step 1: Add new field with new name
$fieldSet = FieldSet::where('model_type', Product::class)
    ->where('set_code', 'default')
    ->first();

$fieldSet->fields()->create([
    'field_name' => 'customer_email',
    'field_type' => FlexyFieldType::STRING->value,
    'sort' => 10,
]);

// Step 2: Copy data
DB::statement("
    INSERT INTO ff_values (model_type, model_id, field_name, value_string, created_at, updated_at)
    SELECT model_type, model_id, 'customer_email', value_string, NOW(), NOW()
    FROM ff_values
    WHERE field_name = 'email'
    ON DUPLICATE KEY UPDATE value_string = VALUES(value_string)
");

// Step 3: Update code to use new field
// $model->flexy->customer_email instead of $model->flexy->email

// Step 4: After deployment, remove old field data
DB::table('ff_values')
    ->where('field_name', 'email')
    ->where('model_type', Product::class)
    ->delete();

$fieldSet->fields()->where('field_name', 'email')->delete();
```

### Changing Field Type

```php
// Example: Change 'price' from STRING to DECIMAL

// Step 1: Create new field with correct type
$fieldSet = FieldSet::where('model_type', Product::class)
    ->where('set_code', 'default')
    ->first();

$fieldSet->fields()->create([
    'field_name' => 'price_new',
    'field_type' => FlexyFieldType::DECIMAL->value,
    'sort' => 1,
]);

// Step 2: Convert and copy data
foreach (Product::cursor() as $product) {
    if (isset($product->flexy->price)) {
        $product->flexy->price_new = (float) $product->flexy->price;
        $product->save();
    }
}

// Step 3: Rename in application code

// Step 4: Clean up old field
$fieldSet->fields()->where('field_name', 'price')->delete();
```

### Data Cleanup

```php
// Remove orphaned values (models that no longer exist)
$validModelIds = Product::pluck('id')->toArray();

DB::table('ff_values')
    ->where('model_type', Product::class)
    ->whereNotIn('model_id', $validModelIds)
    ->delete();

// Remove unused fields (not in any field set)
$fieldSet = FieldSet::where('model_type', Product::class)
    ->where('set_code', 'default')
    ->first();

$validFields = $fieldSet->fields()->pluck('field_name')->toArray();

DB::table('ff_values')
    ->where('model_type', Product::class)
    ->whereNotIn('field_name', $validFields)
    ->delete();
```

## Testing

### Factory Support

Define factories for models with flexy fields:

```php
// database/factories/ProductFactory.php
namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => $this->faker->unique()->bothify('SKU-####-????'),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            // Set flexy fields after model is created (has ID)
            $product->flexy->color = $this->faker->randomElement(['Red', 'Blue', 'Green', 'Black', 'White']);
            $product->flexy->size = $this->faker->randomElement(['S', 'M', 'L', 'XL']);
            $product->flexy->weight_kg = $this->faker->randomFloat(2, 0.1, 10);
            $product->flexy->in_stock = $this->faker->boolean(80);
            $product->save();
        });
    }

    // State for specific scenarios
    public function outOfStock()
    {
        return $this->state(function (array $attributes) {
            return [];
        })->afterCreating(function (Product $product) {
            $product->flexy->in_stock = false;
            $product->save();
        });
    }

    public function heavyProduct()
    {
        return $this->afterCreating(function (Product $product) {
            $product->flexy->weight_kg = $this->faker->randomFloat(2, 50, 100);
            $product->save();
        });
    }
}
```

### Testing Flexy Fields

```php
// tests/Feature/ProductTest.php
namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_can_create_product_with_flexy_fields()
    {
        $product = Product::factory()->create();

        $product->flexy->color = 'Red';
        $product->flexy->size = 'L';
        $product->save();

        $this->assertDatabaseHas('ff_values', [
            'model_type' => Product::class,
            'model_id' => $product->id,
            'field_name' => 'color',
            'value_string' => 'Red',
        ]);

        $fresh = Product::find($product->id);
        $this->assertEquals('Red', $fresh->flexy->color);
        $this->assertEquals('L', $fresh->flexy->size);
    }

    public function test_validates_required_flexy_fields()
    {
        $fieldSet = FieldSet::firstOrCreate([
            'model_type' => Product::class,
            'set_code' => 'default',
        ], [
            'label' => 'Default',
            'is_default' => true,
        ]);

        $fieldSet->fields()->create([
            'field_name' => 'size',
            'field_type' => FlexyFieldType::STRING->value,
            'validation_rules' => 'required',
        ]);

        $product = Product::factory()->create();
        $product->assignToFieldSet('default');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $product->flexy->size = ''; // Required field
        $product->save();
    }

    public function test_can_query_by_flexy_fields()
    {
        Product::factory()->count(5)->create()->each(function ($product) {
            $product->flexy->color = 'Red';
            $product->save();
        });

        Product::factory()->count(3)->create()->each(function ($product) {
            $product->flexy->color = 'Blue';
            $product->save();
        });

        $redProducts = Product::where('flexy_color', 'Red')->get();
        $this->assertCount(5, $redProducts);
    }
}
```

### Performance Testing

```php
public function test_bulk_updates_are_performant()
{
    $products = Product::factory()->count(100)->create();

    $startTime = microtime(true);

    foreach ($products as $product) {
        $product->flexy->in_stock = false;
        $product->save();
    }

    $duration = microtime(true) - $startTime;

    // Should complete in under 5 seconds (view recreated only once)
    $this->assertLessThan(5, $duration);
}
```

## Common Pitfalls

### 1. Forgetting Model ID

```php
// ❌ Wrong: Setting flexy fields before saving model
$product = new Product(['name' => 'Test']);
$product->flexy->color = 'Red'; // Error: model has no ID yet!
$product->save();

// ✅ Correct: Save model first to get ID
$product = new Product(['name' => 'Test']);
$product->save(); // Get ID first
$product->flexy->color = 'Red'; // Now it works
$product->save();

// ✅ Or use create with callback
$product = Product::create(['name' => 'Test']);
$product->update(function ($product) {
    $product->flexy->color = 'Red';
});
```

### 2. Type Confusion

```php
// ❌ Wrong: String '0' vs Integer 0 vs Boolean false
$product->flexy->is_active = '0';      // Stored as string!
$product->flexy->is_active = 0;        // Stored as integer!
$product->flexy->is_active = false;    // Stored as boolean!

// ✅ Correct: Use consistent types
$product->flexy->is_active = false;    // For boolean fields
$product->flexy->quantity = 0;         // For integer fields
$product->flexy->code = '0';           // For string fields (like '007')
```

### 3. Missing Field Set Assignment

```php
// ❌ Dangerous: No field set assignment
class Product extends Model
{
    use Flexy;
    // No field set assigned - any field allowed!
}

$product->flexy->random_typo_field = 'value'; // Silently accepted!

// ✅ Safe: Assign to field set
$product = Product::create(['name' => 'Test']);
$product->assignToFieldSet('default'); // Enforce defined fields only
```

### 4. N+1 Query Problem

```php
// ❌ Wrong: N+1 queries
$products = Product::limit(100)->get();
foreach ($products as $product) {
    echo $product->name;            // OK
    echo $product->flexy->color;    // N+1: Already loaded via pivot view!
}
// Actually, this is OK with FlexyField's global scope!
// But be aware of how it works internally.

// ✅ Better: Understand global scope loads flexy fields automatically
// Just use them!
```

### 5. Large Text in Flexy Fields

```php
// ❌ Wrong: Large text in VARCHAR(255) field
$product->flexy->description = $huge5000CharText; // Truncated!

// ✅ Correct: Use regular TEXT column for large content
Schema::table('products', function (Blueprint $table) {
    $table->text('description');
});
$product->description = $huge5000CharText; // Proper TEXT column
```

### 6. JSON Encoding

```php
// ❌ Wrong: Manual JSON encoding
$product->flexy->metadata = json_encode(['key' => 'value']);
// Later: json_decode($product->flexy->metadata) - double encoded!

// ✅ Correct: Pass array directly
$product->flexy->metadata = ['key' => 'value'];
// Retrieved as: $product->flexy->metadata (already decoded)
```

### 7. View Recreation After Deletion

```php
// ❌ Not needed: View recreation on delete
static::deleted(function ($model) {
    FlexyField::dropAndCreatePivotView(); // Waste of time!
});

// ✅ Correct: No recreation needed (no schema change)
static::deleted(function ($model) {
    // Just delete the values, view is still valid
});
```

## Code Examples

### Complete CRUD Example

```php
// Create
$product = Product::create(['name' => 'T-Shirt', 'sku' => 'TS-001']);
$product->flexy->color = 'Red';
$product->flexy->size = 'M';
$product->flexy->in_stock = true;
$product->save();

// Read
$product = Product::find($id);
echo $product->flexy->color;  // "Red"
echo $product->flexy->size;   // "M"

// Update
$product->flexy->color = 'Blue';
$product->save();

// Delete
$product->delete(); // Flexy values automatically deleted

// Query
$redProducts = Product::where('flexy_color', 'Red')->get();
$mediumProducts = Product::where('flexy_size', 'M')->get();
$redMedium = Product::where('flexy_color', 'Red')
    ->where('flexy_size', 'M')
    ->get();
```

### API Resource Example

```php
// app/Http/Resources/ProductResource.php
class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'attributes' => [
                'color' => $this->flexy->color,
                'size' => $this->flexy->size,
                'weight_kg' => $this->flexy->weight_kg,
                'in_stock' => (bool) $this->flexy->in_stock,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### Search/Filter Example

```php
// app/Http/Controllers/ProductController.php
public function index(Request $request)
{
    $query = Product::query();

    // Filter by flexy fields
    if ($request->has('color')) {
        $query->where('flexy_color', $request->color);
    }

    if ($request->has('size')) {
        $query->where('flexy_size', $request->size);
    }

    if ($request->has('in_stock')) {
        $query->where('flexy_in_stock', $request->boolean('in_stock'));
    }

    // Price range
    if ($request->has('min_price')) {
        $query->where('flexy_price', '>=', $request->min_price);
    }

    if ($request->has('max_price')) {
        $query->where('flexy_price', '<=', $request->max_price);
    }

    return ProductResource::collection(
        $query->paginate(20)
    );
}
```

This guide covers the most important patterns and practices. For more specific use cases, consult the [TROUBLESHOOTING.md](TROUBLESHOOTING.md) guide.
