# FlexyField - Dynamic Model Fields for Laravel

<p align="center">
<a href="https://github.com/aurorawebsoftware/flexyfield/actions"><img src="https://github.com/aurorawebsoftware/flexyfield/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/aurorawebsoftware/flexyfield"><img src="https://img.shields.io/packagist/v/aurorawebsoftware/flexyfield" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/aurorawebsoftware/flexyfield"><img src="https://img.shields.io/packagist/dt/aurorawebsoftware/flexyfield" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/aurorawebsoftware/flexyfield"><img src="https://img.shields.io/packagist/l/aurorawebsoftware/flexyfield" alt="License"></a>
<a href="https://packagist.org/packages/aurorawebsoftware/flexyfield"><img src="https://img.shields.io/packagist/php-v/aurorawebsoftware/flexyfield" alt="PHP Version"></a>
</p>

> **Add dynamic fields to Laravel models without database migrations**

FlexyField enables flexible, type-safe field management for Eloquent models. Perfect for e-commerce catalogs, multi-tenant apps, and CMS platforms where different model instances need different attributes.

## ✨ Features

- 🎯 **Schema-Based Organization** - Different instances can use different field configurations
- 🔒 **Type-Safe Storage** - STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON
- ✅ **Built-in Validation** - Laravel validation rules per schema
- 🔍 **Eloquent Integration** - Query flexy fields with standard `where()` methods
- ⚡ **Performance Optimized** - Smart view recreation (98% faster in v2.0)
- 📦 **Zero Migrations** - Add fields without changing database schema

## 🚀 Why FlexyField?

| Feature | FlexyField | JSON Columns | Custom Tables |
|---------|-----------|--------------|---------------|
| Type Safety | ✅ Separate typed columns | ❌ Everything is JSON | ✅ Yes |
| Validation | ✅ Per-field rules | ⚠️ Manual | ✅ Yes |
| Queryable | ✅ Standard Eloquent | ⚠️ JSON queries | ✅ Yes |
| No Migrations | ✅ Add fields anytime | ✅ Yes | ❌ Requires migrations |
| Multiple Schemas | ✅ Per instance | ❌ No | ❌ No |

**Perfect for:**
- E-commerce (shoes need size/color, books need ISBN/author)
- Multi-tenant apps (different fields per tenant)
- CMS platforms (flexible content types)
- Dynamic forms and configurations

## 📦 Installation

**Requirements:** PHP 8.2+, Laravel 11.x+, MySQL 8.0+ / PostgreSQL 16+

```bash
composer require aurorawebsoftware/flexyfield
php artisan migrate
```

## 🎯 Quick Start

### 1. Enable on Model

```php
use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Traits\Flexy;

class Product extends Model implements FlexyModelContract
{
    use Flexy;
}
```

### 2. Create Schema & Fields

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Create schema
Product::createSchema('footwear', 'Footwear Products', isDefault: true);

// Add validated fields
Product::addFieldToSchema('footwear', 'size', FlexyFieldType::INTEGER, 
    validationRules: 'required|numeric|min:20|max:50');
Product::addFieldToSchema('footwear', 'color', FlexyFieldType::STRING, 
    validationRules: 'required|string|max:50');
```

### 3. Use Flexy Fields

```php
$product = Product::create(['name' => 'Running Shoes']);
$product->assignToSchema('footwear');

// Set values
$product->flexy->size = 42;
$product->flexy->color = 'blue';
$product->flexy->price = 49.90;
$product->save();

// Query
$blueShoes = Product::where('flexy_color', 'blue')->get();
$affordable = Product::where('flexy_price', '<', 100)->get();
```

## 🛒 E-Commerce Example

Here is a practical example of how to use FlexyField in an e-commerce application with `Category`, `Product`, and `Order` models.

### 1. Setup Models

```php
use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Traits\Flexy;
use Illuminate\Database\Eloquent\Model;

class Category extends Model implements FlexyModelContract
{
    use Flexy;
}

class Product extends Model implements FlexyModelContract
{
    use Flexy;
}

class Order extends Model implements FlexyModelContract
{
    use Flexy;
}
```

### 2. Define Schemas & Fields

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// --- Category Schema ---
Category::createSchema('electronics', 'Electronics');
Category::addFieldToSchema('electronics', 'icon_class', FlexyFieldType::STRING);
Category::addFieldToSchema('electronics', 'banner_color', FlexyFieldType::STRING);

// --- Product Schema ---
Product::createSchema('smartphone', 'Smartphone');
Product::addFieldToSchema('smartphone', 'screen_size', FlexyFieldType::DECIMAL);
Product::addFieldToSchema('smartphone', 'battery_capacity', FlexyFieldType::INTEGER);
Product::addFieldToSchema('smartphone', 'has_5g', FlexyFieldType::BOOLEAN);
Product::addFieldToSchema('smartphone', 'release_date', FlexyFieldType::DATE);

// --- Order Schema ---
Order::createSchema('gift_order', 'Gift Order');
Order::addFieldToSchema('gift_order', 'gift_note', FlexyFieldType::STRING);
Order::addFieldToSchema('gift_order', 'is_wrapped', FlexyFieldType::BOOLEAN);
Order::addFieldToSchema('gift_order', 'delivery_instructions', FlexyFieldType::STRING);
```

### 3. Usage in Controller

```php
// Create a Category with custom attributes
$category = Category::create(['name' => 'Smartphones']);
$category->assignToSchema('electronics');
$category->flexy->icon_class = 'fa-mobile';
$category->flexy->banner_color = '#FF5733';
$category->save();

// Create a Product with specific specs
$product = Product::create(['name' => 'iPhone 15', 'category_id' => $category->id]);
$product->assignToSchema('smartphone');
$product->flexy->screen_size = 6.1;
$product->flexy->battery_capacity = 3349;
$product->flexy->has_5g = true;
$product->flexy->release_date = '2023-09-22';
$product->save();

// Create an Order with special instructions
$order = Order::create(['user_id' => 1, 'total' => 999.99]);
$order->assignToSchema('gift_order');
$order->flexy->gift_note = 'Happy Birthday!';
$order->flexy->is_wrapped = true;
$order->save();
```

### 4. Querying

```php
// Find all 5G smartphones released after 2023
$modernPhones = Product::whereSchema('smartphone')
    ->where('flexy_has_5g', true)
    ->where('flexy_release_date', '>=', '2023-01-01')
    ->get();

// Find all gift orders that need wrapping
$ordersToWrap = Order::whereSchema('gift_order')
    ->where('flexy_is_wrapped', true)
    ->get();
```

## 📚 Documentation

- [**Performance Guide**](docs/PERFORMANCE.md) - Optimization, indexing, scaling (v2.0: 98% faster!)
- [**Best Practices**](docs/BEST_PRACTICES.md) - Patterns, validation, common pitfalls
- [**Deployment Guide**](docs/DEPLOYMENT.md) - Production deployment, rollback, monitoring
- [**Troubleshooting**](docs/TROUBLESHOOTING.md) - Common issues & solutions

**Quick Troubleshooting:**
- Field not found? → `$model->assignToSchema('schema_code')`
- Validation errors? → `Product::getFieldsForSchema('schema_code')`
- Slow queries? → See [Performance Guide](docs/PERFORMANCE.md)
- View out of sync? → `php artisan flexyfield:rebuild-view`

## ⚡ Performance

**v2.0 Smart View Recreation:**
- Only recreates database view when NEW fields are added
- **1000 saves = 1-2 view recreations** (vs 1000 in v1.0)
- **~98% performance improvement**

**Recommended Scale:**
- ✅ Small: 1-20 fields, <100K models (Excellent)
- ✅ Medium: 20-50 fields, 100K-1M models (Good)  
- ⚠️ Large: 50-100 fields, 1M-10M models (Acceptable with optimization)

## 🔧 Advanced Features

### Multiple Schemas

```php
// Footwear schema
Product::createSchema('footwear', 'Footwear');
Product::addFieldToSchema('footwear', 'size', FlexyFieldType::INTEGER);

// Books schema
Product::createSchema('books', 'Books');
Product::addFieldToSchema('books', 'isbn', FlexyFieldType::STRING);

// Query by schema
$shoes = Product::whereSchema('footwear')->get();
```

### Field Types

```php
$product->flexy->name = 'Product';              // STRING
$product->flexy->quantity = 100;                // INTEGER
$product->flexy->price = 49.90;                 // DECIMAL
$product->flexy->in_stock = true;               // BOOLEAN
$product->flexy->published_at = Carbon::now();  // DATETIME
$product->flexy->tags = ['summer', 'sale'];     // JSON
```

### Select Options

Restrict field values to predefined options:

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Single select (dropdown)
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'color',
    fieldType: FlexyFieldType::STRING,
    fieldMetadata: ['options' => ['red' => 'Red', 'blue' => 'Blue', 'green' => 'Green']]
);

// Multi-select (checkboxes)
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'features',
    fieldType: FlexyFieldType::JSON,
    fieldMetadata: [
        'options' => ['wifi', '5g', 'nfc', 'bluetooth'],
        'multiple' => true
    ]
);

// Usage
$phone = Product::create(['name' => 'Smartphone']);
$phone->assignToSchema('electronics');
$phone->flexy->color = 'blue';              // Single value
$phone->flexy->features = ['wifi', '5g'];   // Multiple values
$phone->save();
```

### Attribute Grouping

Organize related fields into groups for better UI presentation:

```php
use AuroraWebSoftware\FlexyField\Models\FieldSchema;

// Define fields with groups
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'voltage',
    fieldType: FlexyFieldType::STRING,
    fieldMetadata: ['group' => 'Power Specs']
);

Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'weight_kg',
    fieldType: FlexyFieldType::DECIMAL,
    fieldMetadata: ['group' => 'Physical Dimensions']
);

// Fields without group metadata are ungrouped
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'name',
    fieldType: FlexyFieldType::STRING
);

// Retrieve fields organized by group
$schema = FieldSchema::where('schema_code', 'electronics')->first();
$grouped = $schema->getFieldsGrouped();

// Iterate through groups
foreach ($grouped as $groupName => $fields) {
    echo "Group: $groupName\n";
    foreach ($fields as $field) {
        echo "  - {$field->name}\n";
    }
}
```

### UI Hints

Improve UX with human-readable labels, placeholders, and hints:

```php
use AuroraWebSoftware\FlexyField\Models\SchemaField;

// Define field with UI hints
Product::addFieldToSchema(
    schemaCode: 'electronics',
    fieldName: 'battery_capacity_mah',
    fieldType: FlexyFieldType::INTEGER,
    label: 'Battery Capacity',
    fieldMetadata: [
        'placeholder' => 'Enter value in mAh',
        'hint' => 'Typical range: 1000-5000mAh'
    ]
);

// Retrieve UI hints
$field = SchemaField::where('name', 'battery_capacity_mah')->first();
echo $field->getLabel();        // "Battery Capacity"
echo $field->getPlaceholder();  // "Enter value in mAh"
echo $field->getHint();         // "Typical range: 1000-5000mAh"

// Label falls back to name if null
$field->label = null;
echo $field->getLabel();        // "battery_capacity_mah"
```


```php
try {
    $product->flexy->size = 'invalid';
    $product->save(); // Throws ValidationException
} catch (ValidationException $e) {
    $errors = $e->errors();
}
```

## 🧪 Testing

```bash
./vendor/bin/pest                              # All tests
./vendor/bin/pest --coverage                   # With coverage
./vendor/bin/phpstan analyse                   # Static analysis
./vendor/bin/pint                              # Code style
```

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

**Development:**
```bash
composer install
docker-compose up -d
composer test
```

## 📄 License

MIT License. See [LICENSE.md](LICENSE.md).

---

<p align="center">
<a href="https://github.com/aurorawebsoftware/flexyfield/stargazers">⭐ Star this repo</a> if you find it helpful!
</p>
