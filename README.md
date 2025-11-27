# FlexyField - Dynamic Model Fields for Laravel

[![Tests](https://github.com/aurorawebsoftware/flexyfield/actions/workflows/tests.yml/badge.svg)](https://github.com/aurorawebsoftware/flexyfield/actions/workflows/tests.yml)

**FlexyField** is a dynamic fields package for Laravel, allowing developers to define flexible and customizable fields on models **without needing to change** the database schema.

It enables on-the-fly definition of fields, field types, validation, and value assignment, providing the perfect solution for projects requiring flexible content structures.

## Table of Contents

- [Key Features](#key-features)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [Adding Flexy Fields to a Model](#adding-flexy-fields-to-a-model)
  - [Setting and Retrieving Flexy Fields](#setting-and-retrieving-flexy-fields)
  - [Field Set Management](#field-set-management)
- [Advanced Usage](#advanced-usage)
  - [Querying Models by Dynamic Fields](#querying-models-by-dynamic-fields)
  - [Field Set Validation](#field-set-validation)
  - [Using Dates and Datetimes](#using-dates-and-datetimes)
  - [Field Sorting](#field-sorting)
  - [Querying by Field Set](#querying-by-field-set)
- [Performance Optimization](#performance-optimization)
- [Production Readiness](#production-readiness)
- [Database Migration](#database-migration)
- [Configuration](#configuration)
- [Documentation](#documentation)
- [CI/CD](#cicd)
- [Contribution](#contribution)
- [Running Tests](#running-tests)
- [License](#license)

## Key Features

-   **Field Sets**: Different instances of the same model can use different field configurations (e.g., shoes vs books)
-   Dynamically add fields to any Eloquent model.
-   Supports field types: String, Integer, Decimal, Date, Boolean, DateTime, and JSON.
-   Field-level validation using Laravel's validation rules.
-   Query models by flexible field values using Eloquent's native methods.
-   Supports multiple field types and data storage through a pivot view.
-   Fully integrable with Laravel's native model structure.
-   Production-ready with performance optimization and comprehensive documentation.

## Documentation

Comprehensive guides for production deployment and best practices:

- **[Performance Guide](docs/PERFORMANCE.md)** - Performance characteristics, optimization strategies, indexing, and scaling recommendations
- **[Best Practices](docs/BEST_PRACTICES.md)** - Patterns, conventions, validation strategies, and common pitfalls to avoid
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Pre-deployment checklist, deployment steps, rollback procedures, and monitoring setup
- **[Troubleshooting](docs/TROUBLESHOOTING.md)** - Common issues, debugging techniques, and solutions

**Quick Troubleshooting:**
- **Field not found?** Ensure model is assigned to a field set: `$model->assignToFieldSet('set_code')`
- **Validation errors?** Check field set validation rules: `Product::getFieldsForSet('set_code')`
- **Query performance slow?** See [Performance Guide](docs/PERFORMANCE.md) for optimization tips
- **View out of sync?** Rebuild view: `php artisan flexyfield:rebuild-view`

## Installation

### Prerequisites

- PHP 8.2, 8.3, or 8.4
- Laravel 11.x or 12.x
- MySQL 8.0+ or PostgreSQL 16+ (database views required)

### Install via Composer

```shell
composer require aurorawebsoftware/flexyfield
```

### Run Migrations

After installing, run the provided migrations to create the necessary tables:

```shell
php artisan migrate
```

This creates the following database structure:

- **`ff_field_sets`**: Stores field set definitions per model type
- **`ff_set_fields`**: Stores field definitions within field sets
- **`ff_values`**: Stores the actual values assigned to models' flexy fields
- **`ff_view_schema`**: Tracks field definitions for optimized view recreation
- **`ff_values_pivot_view`**: Database view that pivots all values for efficient querying

## Quick Start

Get up and running with FlexyField in minutes. This section covers the essential steps to add dynamic fields to your models.

### Adding Flexy Fields to a Model

To enable dynamic fields on a model, include the `Flexy` trait and implement the `FlexyModelContract` interface:

```php
<?php

namespace App\Models;

use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Traits\Flexy;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements FlexyModelContract
{
    use Flexy;
    
    // Your existing model code...
}
```

**What this enables:**
- Dynamic field assignment via `$product->flexy->fieldname`
- Field set management and validation
- Querying by flexy field values

### Setting and Retrieving Flexy Fields

Once your model is set up, you can assign and retrieve flexy fields. Fields are accessed through the `flexy` attribute or via the `flexy_` prefix.

**Setting Flexy Fields:**

```php
// Create a product instance
$product = Product::create(['name' => 'Training Shoes']);

// Assign flexy fields with different types
$product->flexy->color = 'blue';                    // String
$product->flexy->price = 49.90;                    // Decimal
$product->flexy->size = 42;                        // Integer
$product->flexy->gender = 'man';                   // String
$product->flexy->in_stock = true;                 // Boolean
$product->flexy->available_coupons = ['summer15', 'white_saturday']; // Array/JSON
$product->save(); // Save the model and flexy fields
```

**Retrieving Flexy Fields:**

```php
// Access via flexy attribute
echo $product->flexy->color;              // 'blue'
echo $product->flexy->size;               // 42
echo $product->flexy->in_stock;           // true

// Or use the flexy_ prefix (works in queries too)
echo $product->flexy_color;                // 'blue'
echo $product->flexy_size;                // 42
echo $product->flexy_in_stock;            // true

// JSON fields are returned as JSON strings (decode if needed)
$coupons = json_decode($product->flexy->available_coupons, true);
```


### Field Set Management

**Field Sets** allow you to organize fields and apply validation rules. Different instances of the same model can use different field sets (e.g., shoes vs books), enabling flexible field configurations per instance.

**Why use Field Sets?**
- Organize related fields together (e.g., all footwear fields in one set)
- Apply validation rules to ensure data integrity
- Control which fields are available for each model instance
- Support different product types with different attributes

#### Creating and Managing Field Sets

**Create a Field Set:**

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Create a field set for footwear products
Product::createFieldSet(
    setCode: 'footwear',
    label: 'Footwear Fields',
    description: 'Fields for shoe products (size, color, etc.)',
    isDefault: false
);

// Create a default field set (new instances auto-assign to this)
Product::createFieldSet(
    setCode: 'default',
    label: 'Default Product Fields',
    description: 'Default fields for all products',
    isDefault: true
);
```

**Add Fields to a Set with Validation:**

```php
// Add fields with validation rules
Product::addFieldToSet('footwear', 'color', FlexyFieldType::STRING, sort: 1, validationRules: 'required|string');
Product::addFieldToSet('footwear', 'size', FlexyFieldType::INTEGER, sort: 2, validationRules: 'numeric|min:20|max:50');
Product::addFieldToSet('footwear', 'in_stock', FlexyFieldType::BOOLEAN, sort: 3, validationRules: 'required|boolean');
Product::addFieldToSet('footwear', 'tags', FlexyFieldType::JSON, sort: 4, validationRules: 'required|array');
```

**Real-World Example: E-commerce Product Types**

```php
// Footwear field set
Product::createFieldSet('footwear', 'Footwear Products', 'Shoes, boots, sandals');
Product::addFieldToSet('footwear', 'size', FlexyFieldType::INTEGER, sort: 1, validationRules: 'required|numeric|min:20|max:50');
Product::addFieldToSet('footwear', 'color', FlexyFieldType::STRING, sort: 2, validationRules: 'required|string|max:50');
Product::addFieldToSet('footwear', 'material', FlexyFieldType::STRING, sort: 3, validationRules: 'required|string');

// Books field set
Product::createFieldSet('books', 'Book Products', 'Books, ebooks, audiobooks');
Product::addFieldToSet('books', 'isbn', FlexyFieldType::STRING, sort: 1, validationRules: 'required|string|size:13');
Product::addFieldToSet('books', 'author', FlexyFieldType::STRING, sort: 2, validationRules: 'required|string|max:255');
Product::addFieldToSet('books', 'pages', FlexyFieldType::INTEGER, sort: 3, validationRules: 'required|numeric|min:1');
```

> **Validation Rules**: All Laravel validation rules are supported. See [Laravel Validation Documentation](https://laravel.com/docs/11.x/validation#available-validation-rules) for complete list.

#### Retrieving and Managing Field Sets

```php
// Get a specific field set
$fieldSet = Product::getFieldSet('footwear');

// Get all field sets for the model
$allSets = Product::getAllFieldSets();

// Get fields for a set
$fields = Product::getFieldsForSet('footwear');

// Remove a field from a set
Product::removeFieldFromSet('footwear', 'color');

// Delete a field set (only if no instances are using it)
Product::deleteFieldSet('footwear');
```

#### Assigning Models to Field Sets

```php
// Create a product
$product = Product::create(['name' => 'Running Shoes']);

// Assign to a field set
$product->assignToFieldSet('footwear');

// New instances automatically assign to default set (if one exists)
$defaultProduct = Product::create(['name' => 'Generic Product']);
// $defaultProduct->field_set_code is automatically set to 'default'

// Get the assigned field set code
$setCode = $product->getFieldSetCode(); // Returns 'footwear'

// Get available fields for this instance
$availableFields = $product->getAvailableFields();

// Change field set assignment
$product->assignToFieldSet('books'); // Only fields from 'books' set are accessible
```


### Saving a Model with Validation

After defining field sets with validation, saving a model with invalid data will throw a `ValidationException`. **Models must be assigned to a field set before setting flexy field values.**

**Validation Example:**

```php
use Illuminate\Validation\ValidationException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldSetNotFoundException;
use AuroraWebSoftware\FlexyField\Exceptions\FieldNotInSetException;

// Create product and assign to field set
$product = Product::create(['name' => 'Running Shoes']);
$product->assignToFieldSet('footwear');

try {
    // Invalid: size must be numeric, but we're setting a string
    $product->flexy->size = 'invalid-size';
    $product->save(); // Throws ValidationException
} catch (ValidationException $e) {
    // Access validation errors
    $errors = $e->errors();
    // Handle validation errors
    foreach ($errors as $field => $messages) {
        echo "{$field}: " . implode(', ', $messages) . "\n";
    }
}

// Trying to set a field not in the assigned set
try {
    $product->flexy->isbn = '1234567890'; // ISBN not in 'footwear' set
    $product->save(); // Throws FieldNotInSetException
} catch (FieldNotInSetException $e) {
    echo "Field 'isbn' is not available in the 'footwear' field set.\n";
    echo "Available fields: " . implode(', ', $product->getAvailableFields()->pluck('field_name')->toArray());
}

// Trying to set fields without field set assignment
try {
    $unassigned = Product::create(['name' => 'Product']);
    $unassigned->flexy->color = 'blue';
    $unassigned->save(); // Throws FieldSetNotFoundException
} catch (FieldSetNotFoundException $e) {
    echo "No field set assigned. Assign a field set first:\n";
    echo "\$product->assignToFieldSet('default');\n";
}
```

**Best Practice:** Always assign models to a field set immediately after creation, or create a default field set that auto-assigns to new instances.


## Advanced Usage

### Querying Models by Dynamic Fields

FlexyField allows you to query models based on their flexy field values using standard Eloquent methods. The package automatically handles joins via global scopes.

**Basic Queries:**

```php
// Find all products with blue color
$products = Product::where('flexy_color', 'blue')->get();

// Use dynamic where methods (Laravel's magic methods)
$products = Product::whereFlexyColor('blue')->get();

// Multiple conditions
$products = Product::where('flexy_color', 'blue')
    ->where('flexy_price', '<', 100)
    ->where('flexy_in_stock', true)
    ->get();
```

**Real-World Example: E-commerce Product Search**

```php
// Find all footwear products in stock, size 42, under $100
$shoes = Product::whereFieldSet('footwear')
    ->where('flexy_size', 42)
    ->where('flexy_in_stock', true)
    ->where('flexy_price', '<', 100)
    ->get();

// Find books by author
$books = Product::whereFieldSet('books')
    ->where('flexy_author', 'like', '%Tolkien%')
    ->get();
```

### Field Set Validation

Flexy fields are validated based on the validation rules defined in their field set. For example:

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Create field set with validation rules
User::createFieldSet('default', 'Default User Fields', isDefault: true);
User::addFieldToSet('default', 'username', FlexyFieldType::STRING, sort: 1, validationRules: 'required|max:20');
User::addFieldToSet('default', 'score', FlexyFieldType::INTEGER, sort: 2, validationRules: 'numeric|min:0|max:100');
User::addFieldToSet('default', 'banned', FlexyFieldType::BOOLEAN, sort: 3, validationRules: 'bool');
```

If a user tries to save invalid data, Laravel's native validation system kicks in and prevents the save:

```php
$user = User::create(['name' => 'John']);
$user->assignToFieldSet('default');
$user->flexy->username = 'too_long_username_exceeding_the_limit';
$user->flexy->score = 120;
$user->flexy->banned = false;
$user->save(); // ValidationException thrown due to invalid data
```

### Using Dates and Datetimes

FlexyField supports handling  `date`  and  `datetime`  field types.

```php
use Carbon\Carbon;

$flexyModel->flexy->event_date = Carbon::now(); // Save current date as a flexy field
$flexyModel->save();

echo $flexyModel->flexy->event_date; // Output the saved date`
```



### Field Sorting

FlexyField allows you to specify a sorting order for fields using the `sort` parameter:

```php
Product::addFieldToSet('default', 'sorted_top_field', FlexyFieldType::STRING, sort: 1);
Product::addFieldToSet('default', 'sorted_bottom_field', FlexyFieldType::STRING, sort: 10);
```

This controls how fields are ordered when retrieved via `getFieldsForSet()` or `getAvailableFields()`.

### Querying by Field Set

You can filter models by their assigned field set:

```php
// Get all products in footwear set
$footwear = Product::whereFieldSet('footwear')->get();

// Get products in multiple sets
$products = Product::whereFieldSetIn(['footwear', 'clothing'])->get();

// Get products without field set assignment
$unassigned = Product::whereFieldSetNull()->get();
```

## Performance Optimization

FlexyField v2.0+ includes significant performance improvements with intelligent view recreation.

### Smart View Recreation

The package uses intelligent change detection to minimize view recreation overhead:

- **Before (v1.0)**: Every save recreated the database view (1000 saves = 1000 recreations)
- **After (v2.0+)**: View only recreates when new fields are added (1000 saves = 1-2 recreations)
- **Performance improvement**: ~98% reduction in overhead

**How it works:**
- The `ff_view_schema` table tracks which fields exist
- View recreation only occurs when a new field name is detected
- Existing saves with known fields skip view recreation entirely

### Manual View Rebuild

You can manually rebuild the pivot view when needed:

```bash
php artisan flexyfield:rebuild-view
```

**When to rebuild:**
- After database restoration from backup
- After manual changes to `ff_values` table
- After deployment to verify view is up-to-date
- If you suspect view is out of sync with actual fields

### Performance Best Practices

1. **Index Your Model Tables**: Ensure `field_set_code` column is indexed
2. **Limit Field Count**: Keep field sets focused (20-50 fields per set is optimal)
3. **Use Field Set Filtering**: Use `whereFieldSet()` to narrow queries
4. **Monitor Query Performance**: Use Laravel's query logging in development

For detailed performance guidance, optimization strategies, and scaling recommendations, see the [Performance Guide](docs/PERFORMANCE.md).

## Production Readiness

FlexyField is production-ready with comprehensive documentation:

- **Performance**: Near-native query performance with proper indexing
- **Scalability**: Tested with 1M+ records and 100+ fields
- **Monitoring**: Built-in support for query logging and health checks
- **Deployment**: Zero-downtime deployment strategies
- **Backup**: Database-first migration approaches

**Recommended Scale:**
- Small: 1-20 fields, up to 100K models (Excellent performance)
- Medium: 20-50 fields, 100K-1M models (Good performance)
- Large: 50-100 fields, 1M-10M models (Acceptable with optimization)

See the [Deployment Guide](docs/DEPLOYMENT.md) for production deployment procedures.

**Verification:**

After migration, verify that all data was migrated correctly:

```php
// Check that field sets were created
$fieldSets = Product::getAllFieldSets();
echo "Field sets created: " . $fieldSets->count() . "\n";

// Verify model assignments
$products = Product::whereNotNull('field_set_code')->count();
echo "Products assigned to field sets: " . $products . "\n";
```

## Database Migration

For models using FlexyField, you need to add a `field_set_code` column:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('field_set_code')->nullable()->index();
            
            $table->foreign('field_set_code')
                ->references('set_code')
                ->on('ff_field_sets')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['field_set_code']);
            $table->dropColumn('field_set_code');
        });
    }
};
```

Or use the provided helper trait:

```php
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
```

## Configuration

You can configure the package to use different database drivers (e.g., MySQL or PostgreSQL) for the flexy field pivot table.

## CI/CD

FlexyField uses GitHub Actions for continuous integration and testing:

- **Test Matrix**: Tests run on PHP 8.2, 8.3, 8.4 with Laravel 11.x and 12.x
- **Database Support**: All tests run on both MySQL 8.0 and PostgreSQL 16
- **Code Quality**: PHPStan static analysis and Pint code style checks
- **Automated Testing**: Tests automatically run on push to main and on pull requests

The CI pipeline ensures compatibility across multiple PHP and Laravel versions, as well as database engines.

## Contribution

Feel free to contribute to the development of  **FlexyField**  by submitting a pull request or opening an issue. Contributions are always welcome to enhance this package.



----


### Running Tests

Before submitting any changes, make sure to run the tests to ensure everything is working as expected.

You can run the tests provided using [Pest](https://pestphp.com/) to ensure everything is working as expected:

```shell
./vendor/bin/pest
```

Tests can be run against different databases by using the appropriate PHPUnit configuration:

```shell
# Run tests with MySQL
./vendor/bin/pest --configuration=phpunit.xml.dist

# Run tests with PostgreSQL
./vendor/bin/pest --configuration=phpunit-postgress.xml.dist
```

#### Code Style

Code style is automatically checked in CI, but you can run Pint locally:

```shell
./vendor/bin/pint
```

#### Static Analysis

Static analysis is automatically checked in CI, but you can run PHPStan locally:

```shell
./vendor/bin/phpstan analyse
```

### CI/CD

All tests, code style checks, and static analysis run automatically via GitHub Actions when you:
- Push to the `main` branch
- Open or update a pull request

The CI pipeline tests against:
- PHP versions: 8.2, 8.3, 8.4
- Laravel versions: 11.x, 12.x
- Databases: MySQL 8.0 and PostgreSQL 16

## License

The FlexyField package is open-sourced software licensed under the  MIT License.

----------
