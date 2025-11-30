# Change: Add Simple Localization Support

## Why
PIM and CMS systems require storing content in multiple languages (e.g., Product Description in EN and TR). Currently, users have to create separate fields (`desc_en`, `desc_tr`), which is not scalable.

## What Changes
- **Concept:** Use the existing `JSON` field type to store localized data (e.g., `{"en": "Blue", "tr": "Mavi"}`).
- **Metadata Update:** Add `is_translatable: true` flag to `SchemaField` metadata.
- **Accessor:** Update `Flexy` trait or `Value` model to handle localized retrieval.
    - `$model->flexy->desc` could return the value for the current app locale.
    - `$model->flexy->getTranslation('desc', 'en')` for specific locale.
- **Validation:** Update validation logic to validate values inside the JSON structure (e.g., `required` means at least default locale must be present).

## Usage Example

```php
use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;

// Define translatable field
Product::addFieldToSchema(
    schemaCode: 'product',
    fieldName: 'description',
    fieldType: FlexyFieldType::JSON, // Must be JSON
    fieldMetadata: ['is_translatable' => true]
);

// Usage
app()->setLocale('en');
$product->flexy->description = 'Blue Shirt'; // Sets {"en": "Blue Shirt"}

app()->setLocale('tr');
$product->flexy->description = 'Mavi Gömlek'; // Merges: {"en": "Blue Shirt", "tr": "Mavi Gömlek"}

// Retrieval
echo $product->flexy->description; // Output: "Mavi Gömlek" (current locale)
echo $product->flexy->getTranslation('description', 'en'); // Output: "Blue Shirt"
```

## Constraints
- **Field Type:** Must be `FlexyFieldType::JSON`.
- **Storage:** Stored as JSON object `{"locale": "value"}`.
- **Validation:** `required` rule checks if the JSON is not empty.

## Impact
- **Affected specs:** `type-system`, `field-validation`
- **Affected code:**
    - `src/Models/SchemaField.php`: Add `isTranslatable()` helper.
    - `src/Traits/Flexy.php`: Update accessor logic to handle translatable JSON fields.
- **Breaking changes:** None.
- **Database changes:** None (uses existing `JSON` type and `metadata`).

## Requirements
- **Clean Architecture:** Logic should be encapsulated in Traits or a dedicated Value Object if complex.
- **Testing:**
    - Comprehensive feature tests in `tests/Feature/SimpleLocalizationTest.php`.
    - Test locale switching and fallback.
- **Quality:**
    - Must pass `phpstan` (Larastan) level 5+.
    - Must pass `pint` style checks.

