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

## Impact
- **Affected specs:** `type-system`, `field-validation`
- **Affected code:**
    - `src/Models/SchemaField.php`: Add `isTranslatable()` helper.
    - `src/Traits/Flexy.php`: Update accessor logic to handle translatable JSON fields.
- **Breaking changes:** None.
- **Database changes:** None (uses existing `JSON` type and `metadata`).
