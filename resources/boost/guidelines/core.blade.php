## FlexyField

FlexyField enables dynamic field management for Eloquent models without database schema changes. It uses Field Sets to organize fields and provides type-safe storage with validation.

### Core Concepts

- **Field Sets**: Collections of field definitions assigned to model instances (e.g., 'footwear' vs 'books' sets for Product model)
- **Type-Safe Storage**: Values stored in typed columns (STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON)
- **Validation**: Laravel validation rules enforced per field set
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

### Create Field Sets

Field sets organize related fields. Different model instances can use different sets:

@verbatim
<code-snippet name="Create Field Set" lang="php">
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Create field set
Product::createFieldSet(
    setCode: 'footwear',
    label: 'Footwear Fields',
    description: 'Fields for shoe products',
    isDefault: false
);

// Add fields with validation
Product::addFieldToSet('footwear', 'size', FlexyFieldType::INTEGER, 
    sort: 1, 
    validationRules: 'required|numeric|min:20|max:50'
);
Product::addFieldToSet('footwear', 'color', FlexyFieldType::STRING, 
    sort: 2, 
    validationRules: 'required|string|max:50'
);
</code-snippet>
@endverbatim

### Assign Model to Field Set

Models must be assigned to a field set before setting flexy values:

@verbatim
<code-snippet name="Assign to Field Set" lang="php">
$product = Product::create(['name' => 'Running Shoes']);
$product->assignToFieldSet('footwear');

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

// Filter by field set
Product::whereFieldSet('footwear')->get();
</code-snippet>
@endverbatim

### Field Types

Supported types: STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON:

@verbatim
<code-snippet name="Field Types" lang="php">
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use DateTime;

// String
$product->flexy->name = 'Product Name';

// Integer
$product->flexy->quantity = 100;

// Decimal
$product->flexy->price = 49.90;

// Boolean
$product->flexy->in_stock = true;

// Date/DateTime (DateTime or Carbon instances)
$product->flexy->published_at = new DateTime('2024-01-15 10:30:00');
// Carbon also works: $product->flexy->published_at = Carbon::now();

// JSON (arrays/objects)
$product->flexy->tags = ['summer', 'sale'];
</code-snippet>
@endverbatim

### Validation

Validation is enforced when saving. Models must be assigned to field set first:

@verbatim
<code-snippet name="Validation" lang="php">
$product = Product::create(['name' => 'Shoes']);
$product->assignToFieldSet('footwear');

try {
    $product->flexy->size = 'invalid'; // Throws ValidationException
    $product->save();
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->errors();
}
</code-snippet>
@endverbatim

### Best Practices

1. **Always assign field set before setting values**: `$model->assignToFieldSet('set_code')`
2. **Create default field set**: Set `isDefault: true` for auto-assignment to new instances
3. **Use descriptive set codes**: Use kebab-case like 'footwear', 'books', 'clothing'
4. **Keep field sets focused**: 20-50 fields per set is optimal for performance
5. **Index model tables**: Add index on `field_set_code` column for better query performance

### Common Patterns

**E-commerce Product Types:**

@verbatim
<code-snippet name="E-commerce Pattern" lang="php">
// Footwear set
Product::createFieldSet(
    setCode: 'footwear',
    label: 'Footwear Products'
);
Product::addFieldToSet('footwear', 'size', FlexyFieldType::INTEGER, 
    sort: 1,
    validationRules: 'required|numeric'
);
Product::addFieldToSet('footwear', 'color', FlexyFieldType::STRING, 
    sort: 2,
    validationRules: 'required|string'
);

// Books set
Product::createFieldSet(
    setCode: 'books',
    label: 'Book Products'
);
Product::addFieldToSet('books', 'isbn', FlexyFieldType::STRING, 
    sort: 1,
    validationRules: 'required|string|size:13'
);
Product::addFieldToSet('books', 'author', FlexyFieldType::STRING, 
    sort: 2,
    validationRules: 'required|string|max:255'
);
</code-snippet>
@endverbatim

### Database Migration

Add `field_set_code` column to models using FlexyField:

@verbatim
<code-snippet name="Migration Helper" lang="php">
use AuroraWebSoftware\FlexyField\Database\Migrations\Concerns\AddFieldSetCodeColumn;

return new class extends Migration
{
    use AddFieldSetCodeColumn;

    public function up(): void
    {
        $this->addFieldSetCodeColumn('products');
    }

    public function down(): void
    {
        $this->dropFieldSetCodeColumn('products');
    }
};
</code-snippet>
@endverbatim

### Important Notes

- Models must implement `FlexyModelContract` and use `Flexy` trait
- Field set assignment is required before setting flexy values (or create a default field set)
- Validation uses Laravel's standard validation rules
- Query performance is optimized via database views (`ff_values_pivot_view`)
- Field types are strongly typed (separate columns per type)
- The `flexy_` prefix is used in queries (e.g., `where('flexy_color', 'blue')`), not for direct attribute access
- Use `$model->flexy->fieldname` for reading/writing values
- Use `where('flexy_fieldname', value)` or `whereFlexyFieldname(value)` for querying

