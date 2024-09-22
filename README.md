
# FlexyField - Dynamic Model Fields for Laravel

**FlexyField**  is a dynamic fields package for Laravel, allowing developers to define flexible and customizable fields on models **without needing to change** the database schema.

It enables on-the-fly definition of fields, field types, validation, and value assignment, providing the perfect solution for projects requiring flexible content structures.

## Key Features

-   Dynamically add fields to any Eloquent model.
-   Supports field types: String, Integer, Decimal, Date, Boolean and DateTime.
-   Field-level validation using Laravel’s validation rules.
-   Query models by flexible field values using Elequent's native methods.
-   Supports multiple field types and data storage through a pivot view.
-   Fully integrable with Laravel's native model structure.

## Installation

To install the package via Composer, run:

```shell
composer require aurorawebsoftware/flexyfield
```


After installing, run the provided migrations to create the necessary tables:

```shell
php artisan migrate
```


### Database Structure

The package creates two main tables and one view:

-   `ff_shapes`: Holds the shapes definitions of fields with validation rules and validation messages
-   `ff_values`: Stores the actual values assigned to models' flexy fields.
-   `ff_values_pivot_view` : Views all values as a pivot table

## Quick Start

### Adding Flexy Fields to a Model

To start using Flexy fields, simply include the  `Flexy`  trait and implement the  `FlexyModelContract`  in your model.

```php
use AuroraWebSoftware\FlexyField\Contracts\FlexyModelContract;
use AuroraWebSoftware\FlexyField\Traits\Flexy;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements FlexyModelContract {
    use Flexy;
    
    // your class implementation
}
```

This enables the model to support dynamically assigned fields.

### Setting and Retrieving Flexy Fields

Once the model is set up, you can define and assign flexy fields to it. Fields are assigned through the  `flexy`  attribute.

```php
$product = Product::create(['name' => 'Training Shoes']);

// Set flexy fields
$product->flexy->color = 'blue'; // string
$product->flexy->price = 49.90; // decimal value
$product->flexy->size = 42; // integer
$product->flexy->gender = 'man'; // string
$product->flexy->in_stock = true; // boolean
$product->flexy->availables_coupons = ['summer15', 'white_saturday']; // array, json
$product->save();

// Retrieve the flexy fields using flexy attribute
echo $product->flexy->color; // Outputs 'blue'
echo $product->flexy->size; // Outputs 42`
echo $product->flexy->in_stock; // Outputs true`
echo $product->flexy->availables_coupons; // json string, must be decoded

// or retrieve the flexy fields using default models' attribute with flexy_ prefix
echo $product->flexy_color; // Outputs 'blue'
echo $product->flexy_size; // Outputs 42`
echo $product->flexy_in_stock; // Outputs true`

```


### Defining Model Shapes for Validation

A shape is a field definition with a type and validation rules. Each Model can have one shape.
You can define shapes dynamically with `setFlexyShape()` for fields to apply validation.

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

Product::setFlexyShape('color', FlexyFieldType::STRING, 1, 'required');
Product::setFlexyShape('size', FlexyFieldType::INTEGER, 2, 'numeric|min:20');
Product::setFlexyShape('in_stock', FlexyFieldType::BOOLEAN, 3, 'required|bool');
Product::setFlexyShape('availables_coupons', FlexyFieldType::JSON, 3, 'required');
```

This ensures that when saving the  `color`  field, it must be a required, and the  `size`  field must be a number greater than or equal to 20.
> all available validation rules:
> https://laravel.com/docs/11.x/validation#available-validation-rules


To retrive and delete a model shape
```php
Product::getFlexyShape('color'); // returns Shape model
Product::deleteFlexyShape('color');
```


### Saving a Model with Validation

After defining shapes with validation, saving a model with invalid data will throw a  `ValidationException`.

```php
use Illuminate\Validation\ValidationException;

try {
    $product->flexy->size = 'invalid-size';
    $product->save(); // Throws ValidationException
} catch (ValidationException $e) {
    // Handle the exception
    echo "Validation failed: " . $e->getMessage();
}
```


## Advanced Usage

### Querying Models by Dynamic Fields

FlexyField allows you to query models based on their flexy field values. Here's an example of how to filter models using their Flexy fields:

```php
// Find all blue products
$products = Product::where('flexy_color', 'blue')->get();

// or dynamic where
$products = Product::whereFlexyColor('blue')->get();

// Find models with multiple conditions on flexy fields
$models = Product::where('flexy_color', 'blue 1')
    ->where('flexy_price', '<',  100)
    ->where('flexy_in_stock', true)
    ->get();
```

### Dynamic Validation

Flexy fields can be validated using the shapes you define. For example, you can set validation rules like:

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

User::setFlexyShape('username', FlexyFieldType::STRING, 1, 'required|max:20');
User::setFlexyShape('score', FlexyFieldType::INTEGER, 2, 'numeric|min:0|max:100');
User::setFlexyShape('banned', FlexyFieldType::BOOLEAN, 3, 'bool');
```


If a user tries to save invalid data, Laravel's native validation system kicks in and prevents the save:

```php
$user->flexy->username = 'too_long_username_exceeding_the_limit';
$user->flexy->score = 120;
$user->flexy->banned = false;
$user->save(); // ValidationException thrown due to invalid data`
```

### Using Dates and Datetimes

FlexyField supports handling  `date`  and  `datetime`  field types.

```php
use Carbon\Carbon;

$flexyModel->flexy->event_date = Carbon::now(); // Save current date as a flexy field
$flexyModel->save();

echo $flexyModel->flexy->event_date; // Output the saved date`
```



### Dynamic Field Sorting

FlexyField allows you to specify a sorting order for fields using the  `sort`  parameter:

```php
ExampleFlexyModel::setFlexyShape('sorted_top_field', FlexyFieldType::STRING, 1);
ExampleFlexyModel::setFlexyShape('sorted_bottom_field', FlexyFieldType::STRING, 10);
```


This controls how fields are ordered when retrieved or displayed.



## Configuration

You can configure the package to use different database drivers (e.g., MySQL or PostgreSQL) for the flexy field pivot table.
## Contribution

Feel free to contribute to the development of  **FlexyField**  by submitting a pull request or opening an issue. Contributions are always welcome to enhance this package.



----


### Running Tests

Before submitting any changes, make sure to run the tests to ensure everything is working as expected:

You can run the tests provided using  [Pest](https://pestphp.com/)  to ensure everything is working as expected.

```shell
./vendor/bin/pest
```

#### Code Style

```shell
./vendor/bin/pint
```

#### Static Analyze

```shell
./vendor/bin/phpstan analyse
```

## License

The FlexyField package is open-sourced software licensed under the  MIT License.

----------
