## FlexyField

FlexyField enables dynamic field management for Eloquent models without database schema changes. It uses Schemas to organize fields and provides type-safe storage with validation.

### 🎯 Quick Reference

@verbatim
<code-snippet name="Quick Reference" lang="php">
// 1. Enable on model: use Flexy trait, implements FlexyModelContract
// 2. Create schema: Product::createSchema('code', 'Label', isDefault: true)
// 3. Add fields: Product::addFieldToSchema('code', 'name', FlexyFieldType::STRING)
// 4. Assign model: $model->assignToSchema('code')
// 5. Set values: $model->flexy->field = value
// 6. Query: Product::where('flexy_field', value)
</code-snippet>
@endverbatim

### Core Concepts

- **Schemas**: Collections of field definitions assigned to model instances (e.g., 'footwear' vs 'books' schemas for Product model)
- **Type-Safe Storage**: Values stored in typed columns (STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON)
- **Validation**: Laravel validation rules enforced per schema
- **Query Integration**: Query flexy fields using standard Eloquent methods

### Setup Model

Models must use `Flexy` trait and implement `FlexyModelContract`:

@verbatim
<code-snippet name="Enable FlexyField on Model" lang="php">
use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Traits\Flexy;

class Product extends Model implements FlexyModelContract
{
    use Flexy;
}
</code-snippet>
@endverbatim

### Create Schemas

Schemas organize related fields. Different model instances can use different schemas:

@verbatim
<code-snippet name="Create Schema" lang="php">
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Create schema
Product::createSchema(
    schemaCode: 'footwear',
    label: 'Footwear Fields',
    description: 'Fields for shoe products',
    isDefault: false
);

// Add fields with validation
Product::addFieldToSchema('footwear', 'size', FlexyFieldType::INTEGER, 
    sort: 1, 
    validationRules: 'required|numeric|min:20|max:50'
);
Product::addFieldToSchema('footwear', 'color', FlexyFieldType::STRING, 
    sort: 2, 
    validationRules: 'required|string|max:50'
);
</code-snippet>
@endverbatim

### Assign Model to Schema

Models must be assigned to a schema before setting flexy values:

@verbatim
<code-snippet name="Assign to Schema" lang="php">
$product = Product::create(['name' => 'Running Shoes']);
$product->assignToSchema('footwear');

// Now you can set flexy fields
$product->flexy->size = 42;
$product->flexy->color = 'blue';
$product->save();
</code-snippet>
@endverbatim

### Access Flexy Fields

Access fields via `flexy` attribute. The `flexy_` prefix is used for querying, not direct attribute access:

@verbatim
<code-snippet name="Access Flexy Fields" lang="php">
// Set values
$product->flexy->color = 'blue';
$product->flexy->price = 49.90;
$product->flexy->in_stock = true;
$product->save();

// Retrieve values
echo $product->flexy->color;        // 'blue'
echo $product->flexy->price;        // 49.90
echo $product->flexy->in_stock;     // true
</code-snippet>
@endverbatim

### Query by Flexy Fields

Query models using standard Eloquent methods:

@verbatim
<code-snippet name="Query Flexy Fields" lang="php">
// Basic query
Product::where('flexy_color', 'blue')->get();

// Dynamic where method
Product::whereFlexyColor('blue')->get();

// Multiple conditions
Product::where('flexy_color', 'blue')
    ->where('flexy_price', '<', 100)
    ->where('flexy_in_stock', true)
    ->get();

// Filter by schema
Product::whereSchema('footwear')->get();
</code-snippet>
@endverbatim

### Field Types

Supported types: STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON:

@verbatim
<code-snippet name="Field Types" lang="php">
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use Carbon\Carbon;

// String
$product->flexy->name = 'Product Name';

// Integer
$product->flexy->quantity = 100;

// Decimal
$product->flexy->price = 49.90;

// Boolean
$product->flexy->in_stock = true;

// DateTime (DateTime or Carbon instances)
$product->flexy->published_at = Carbon::now();

// JSON (arrays/objects)
$product->flexy->tags = ['summer', 'sale'];
$product->flexy->metadata = ['featured' => true, 'priority' => 1];
</code-snippet>
@endverbatim

### Select Options

Restrict field values to predefined options using metadata. Supports both single and multi-select:

@verbatim
<code-snippet name="Single Select Options" lang="php">
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Indexed array (values are both keys and labels)
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'size',
    fieldType: FlexyFieldType::STRING,
    fieldMetadata: ['options' => ['S', 'M', 'L', 'XL']]
);

// Associative array (keys stored, values for display)
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'color',
    fieldType: FlexyFieldType::STRING,
    fieldMetadata: ['options' => ['red' => 'Red', 'blue' => 'Blue', 'green' => 'Green']]
);

// Usage
$product->flexy->color = 'blue';  // Valid
$product->flexy->color = 'yellow'; // ValidationException: not in options
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Multi-Select Options" lang="php">
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Multi-select requires FlexyFieldType::JSON
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'features',
    fieldType: FlexyFieldType::JSON, // MUST be JSON type
    fieldMetadata: [
        'options' => ['wifi', '5g', 'nfc', 'bluetooth'],
        'multiple' => true  // Enable multi-select
    ]
);

// Usage
$product->flexy->features = ['wifi', '5g'];      // Valid array
$product->flexy->features = [];                  // Valid empty array
$product->flexy->features = 'wifi';              // ValidationException: not an array
$product->flexy->features = ['wifi', 'invalid']; // ValidationException: 'invalid' not in options
</code-snippet>
@endverbatim

**Important Rules:**
- Multi-select fields MUST use `FlexyFieldType::JSON` type
- Options validation works automatically when `options` metadata is present
- Empty `options` array or missing `options` key means no restrictions
- For indexed arrays, values are used for both storage and validation
- For associative arrays, keys are used for storage and validation

### Validation

Validation is enforced when saving. Models must be assigned to schema first:

@verbatim
<code-snippet name="Validation" lang="php">
use Illuminate\Validation\ValidationException;

$product = Product::create(['name' => 'Shoes']);
$product->assignToSchema('footwear');

try {
    $product->flexy->size = 'invalid'; // Throws ValidationException
    $product->save();
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->errors();
    // $errors = ['flexy.size' => ['The size must be a number.']]
}
</code-snippet>
@endverbatim

### Blade Integration

Display and handle flexy fields in Blade templates:

@verbatim
<code-snippet name="Display in Blade" lang="blade">
{{-- Display flexy field --}}
<p>Color: {{ $product->flexy->color }}</p>
<p>Price: ${{ number_format($product->flexy->price, 2) }}</p>
<p>In Stock: {{ $product->flexy->in_stock ? 'Yes' : 'No' }}</p>

{{-- Loop through products --}}
@foreach($products as $product)
    <div class="product">
        <h3>{{ $product->name }}</h3>
        <span class="size">Size: {{ $product->flexy->size }}</span>
        <span class="color">{{ $product->flexy->color }}</span>
    </div>
@endforeach
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Forms with Flexy Fields" lang="blade">
{{-- Form input --}}
<form method="POST" action="{{ route('products.update', $product) }}">
    @csrf
    @method('PUT')
    
    <div class="form-group">
        <label for="color">Color</label>
        <input type="text" 
               name="flexy[color]" 
               id="color"
               value="{{ old('flexy.color', $product->flexy->color) }}"
               class="@error('flexy.color') is-invalid @enderror">
        
        @error('flexy.color')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="size">Size</label>
        <input type="number" 
               name="flexy[size]" 
               id="size"
               value="{{ old('flexy.size', $product->flexy->size) }}">
        
        @error('flexy.size')
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
    
    <button type="submit">Update</button>
</form>
</code-snippet>
@endverbatim

### ❌ Common Mistakes

@verbatim
<code-snippet name="Common Mistakes to Avoid" lang="php">
// ❌ WRONG: Setting values before schema assignment
$product = Product::create(['name' => 'Shoes']);
$product->flexy->size = 42; // Exception: No schema assigned!

// ✅ CORRECT: Assign schema first
$product = Product::create(['name' => 'Shoes']);
$product->assignToSchema('footwear');
$product->flexy->size = 42;

// ❌ WRONG: Using flexy_ prefix for direct access
echo $product->flexy_color; // May not work as expected

// ✅ CORRECT: Use flexy object for access
echo $product->flexy->color;

// ❌ WRONG: Using flexy object in queries
Product::where('flexy->color', 'blue'); // Doesn't work!

// ✅ CORRECT: Use flexy_ prefix in queries
Product::where('flexy_color', 'blue')->get();

// ❌ WRONG: Forgetting to save
$product->flexy->color = 'blue';
// Values not persisted!

// ✅ CORRECT: Always save after setting values
$product->flexy->color = 'blue';
$product->save();
</code-snippet>
@endverbatim

### 🔍 Troubleshooting

@verbatim
<code-snippet name="Common Exceptions and Solutions" lang="php">
// Exception: SchemaNotFoundException
// Cause: Model not assigned to schema
// Solution:
$product->assignToSchema('your_schema_code');

// Exception: FieldNotInSchemaException  
// Cause: Trying to set field not defined in assigned schema
// Solution: Check available fields
$fields = $product->getAvailableFields();
// Or add field to schema
Product::addFieldToSchema('footwear', 'new_field', FlexyFieldType::STRING);

// Exception: ValidationException
// Cause: Field value doesn't pass validation rules
// Solution: Check validation rules
$schema = Product::getSchema('footwear');
$field = $schema->fields()->where('name', 'size')->first();
echo $field->validation_rules; // 'required|numeric|min:20|max:50'
</code-snippet>
@endverbatim

### ⚡ Performance Tips

@verbatim
<code-snippet name="Performance Optimization" lang="php">
// ✅ Smart View Recreation (v2.0+)
// View only recreates when NEW fields are added
// 1000 saves with existing fields = 1-2 recreations (not 1000!)

// ✅ Keep schemas focused
// Optimal: 20-50 fields per schema
// Acceptable: Up to 100 fields
// Avoid: 100+ fields (consider splitting schemas)

// ✅ Index your model table
Schema::table('products', function (Blueprint $table) {
    $table->index('schema_code'); // Important for performance
});

// ✅ Use eager loading with queries
$products = Product::whereSchema('footwear')
    ->where('flexy_price', '<', 100)
    ->get();
// Flexy fields are automatically eager loaded via pivot view

// ✅ Manual view rebuild if needed
php artisan flexyfield:rebuild-view
</code-snippet>
@endverbatim

### Best Practices

1. **Always assign schema before setting values**: `$model->assignToSchema('schema_code')`
2. **Create default schema**: Set `isDefault: true` for auto-assignment to new instances
3. **Use descriptive schema codes**: Use kebab-case like 'footwear', 'books', 'clothing'
4. **Keep schemas focused**: 20-50 fields per schema is optimal for performance
5. **Index model tables**: Add index on `schema_code` column for better query performance
6. **Use proper validation**: Define validation rules in schema for data integrity
7. **Always include tests**: Every proposal must include comprehensive testing requirements
8. **Always update documentation**: Every proposal must include documentation updates for README.md and Laravel Boost core.blade.php

### Testing Requirements for New Features

All new features must include comprehensive tests:

@verbatim
<code-snippet name="Testing Example" lang="php">
// Unit test example
it('can create a schema with fields', function () {
    $schema = Product::createSchema('test', 'Test Schema');
    Product::addFieldToSchema('test', 'name', FlexyFieldType::STRING);
    
    expect($schema->fields)->toHaveCount(1);
    expect($schema->fields->first()->name)->toBe('name');
});

// Feature test example
it('can assign and retrieve flexy field values', function () {
    $product = Product::create(['name' => 'Test Product']);
    $product->assignToSchema('test');
    $product->flexy->name = 'Test Name';
    $product->save();
    
    expect($product->flexy->name)->toBe('Test Name');
});
</code-snippet>
@endverbatim

### Documentation Requirements for New Features

All new features must update documentation:

1. **README.md**: Add feature documentation with examples
2. **Laravel Boost**: Update this file with AI guidance
3. **Code Examples**: Provide practical, tested examples
4. **Changelog**: Document breaking changes and new features

### Common Patterns

**E-commerce Product Types:**

@verbatim
<code-snippet name="E-commerce Pattern" lang="php">
// Footwear schema
Product::createSchema(
    schemaCode: 'footwear',
    label: 'Footwear Products',
    isDefault: false
);
Product::addFieldToSchema('footwear', 'size', FlexyFieldType::INTEGER, 
    sort: 1,
    validationRules: 'required|numeric|min:20|max:50'
);
Product::addFieldToSchema('footwear', 'color', FlexyFieldType::STRING, 
    sort: 2,
    validationRules: 'required|string|max:50'
);
Product::addFieldToSchema('footwear', 'material', FlexyFieldType::STRING, 
    sort: 3,
    validationRules: 'required|string'
);

// Books schema
Product::createSchema(
    schemaCode: 'books',
    label: 'Book Products',
    isDefault: false
);
Product::addFieldToSchema('books', 'isbn', FlexyFieldType::STRING, 
    sort: 1,
    validationRules: 'required|string|size:13'
);
Product::addFieldToSchema('books', 'author', FlexyFieldType::STRING, 
    sort: 2,
    validationRules: 'required|string|max:255'
);
Product::addFieldToSchema('books', 'pages', FlexyFieldType::INTEGER, 
    sort: 3,
    validationRules: 'required|numeric|min:1'
);

// Usage
$shoe = Product::create(['name' => 'Running Shoes', 'sku' => 'RS-001']);
$shoe->assignToSchema('footwear');
$shoe->flexy->size = 42;
$shoe->flexy->color = 'blue';
$shoe->flexy->material = 'mesh';
$shoe->save();

$book = Product::create(['name' => 'Laravel Guide', 'sku' => 'BK-001']);
$book->assignToSchema('books');
$book->flexy->isbn = '9781234567890';
$book->flexy->author = 'John Doe';
$book->flexy->pages = 350;
$book->save();
</code-snippet>
@endverbatim

### Controller Example

@verbatim
<code-snippet name="Controller with FlexyField" lang="php">
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        // Validate regular fields
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'schema_code' => 'required|exists:ff_schemas,schema_code',
        ]);
        
        // Create product
        $product = Product::create($validated);
        $product->assignToSchema($validated['schema_code']);
        
        // Set flexy fields from request
        if ($request->has('flexy')) {
            foreach ($request->input('flexy') as $field => $value) {
                $product->flexy->$field = $value;
            }
        }
        
        try {
            $product->save();
            return redirect()->route('products.show', $product)
                ->with('success', 'Product created successfully');
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }
    
    public function update(Request $request, Product $product)
    {
        $product->update($request->only(['name', 'sku']));
        
        if ($request->has('flexy')) {
            foreach ($request->input('flexy') as $field => $value) {
                $product->flexy->$field = $value;
            }
            
            try {
                $product->save();
            } catch (ValidationException $e) {
                return back()
                    ->withErrors($e->errors())
                    ->withInput();
            }
        }
        
        return redirect()->route('products.show', $product)
            ->with('success', 'Product updated successfully');
    }
}
</code-snippet>
@endverbatim

### Database Migration

Add `schema_code` column to models using FlexyField:

@verbatim
<code-snippet name="Migration Helper" lang="php">
use AuroraWebSoftware\FlexyField\Database\Migrations\Concerns\AddSchemaCodeColumn;

return new class extends Migration
{
    use AddSchemaCodeColumn;

    public function up(): void
    {
        $this->addSchemaCodeColumn('products');
    }

    public function down(): void
    {
        $this->dropSchemaCodeColumn('products');
    }
};
</code-snippet>
@endverbatim

### Important Notes

- Models must implement `FlexyModelContract` and use `Flexy` trait
- Schema assignment is required before setting flexy values (or create a default schema)
- Validation uses Laravel's standard validation rules
- Query performance is optimized via database views (`ff_values_pivot_view`)
- Field types are strongly typed (separate columns per type)
- The `flexy_` prefix is used in queries (e.g., `where('flexy_color', 'blue')`), not for direct attribute access
- Use `$model->flexy->fieldname` for reading/writing values
- Use `where('flexy_fieldname', value)` or `whereFlexyFieldname(value)` for querying
- View is automatically recreated when new fields are added (v2.0+ optimization)
