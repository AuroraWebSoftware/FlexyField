# Design: Simple Localization Support

## Goal
Enable storing and retrieving localized content (e.g., product descriptions in multiple languages) within a single field, without creating separate fields for each language.

## Technical Approach

### 1. Storage Strategy
We will use the existing `FlexyFieldType::JSON` type to store localized data.
- **Database:** Stored in `value_json` column in `ff_field_values`.
- **Format:** `{"en": "Blue", "tr": "Mavi", "de": "Blau"}`

### 2. Metadata Configuration
A new flag `is_translatable` in the `metadata` JSON column of `ff_schema_fields` will indicate if a field is localized.

```json
{
  "is_translatable": true
}
```

### 3. Accessor Logic (The "Magic")
We need to intercept access to the field value in `src/Traits/Flexy.php` (specifically `__get`).

- **Current Behavior:** Returns the raw value (string or array).
- **New Behavior:**
    - If field is `is_translatable`:
        - `__get('desc')`: Returns value for current app locale (`app()->getLocale()`).
        - If current locale missing, fallback to fallback locale.
        - If both missing, return `null` or empty string.

### 4. Explicit Retrieval
We need a way to get the raw translations or a specific locale.
- `$model->flexy->getTranslation('desc', 'tr')`
- `$model->flexy->getTranslations('desc')` // Returns full array

### 5. Setting Values
- **Direct Assignment:** `$model->flexy->desc = 'Blue'` -> Sets value for *current locale*, merging with existing JSON.
- **Bulk Assignment:** `$model->flexy->desc = ['en' => 'Blue', 'tr' => 'Mavi']` -> Replaces/Merges based on array input.

### 6. Validation
Validation needs to be locale-aware.
- `required`: At least one locale (or default locale) must have a value.
- `string`, `min`, `max`: Applied to the value of the *current locale* or all locales?
    - *Decision:* Apply rules to the *values* within the JSON.

## Constraints
- Translatable fields **MUST** be `FlexyFieldType::JSON`.
- Keys in JSON must be valid locale strings (2-5 chars).

## Architecture
- **SchemaField Model:** Add `isTranslatable()` helper.
- **Flexy Trait:** Update `__get` and `__set` logic to handle translation merging and retrieval.
