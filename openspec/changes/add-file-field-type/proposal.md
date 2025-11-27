# Change: Add File Field Type

## Why
Users need to store file uploads (documents, images, PDFs, etc.) as flexy fields. Currently, the system only supports primitive types (STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON) and cannot handle file uploads. Adding a FILE field type will enable use cases such as:
- Product catalogs with downloadable documents
- User profiles with avatar images
- Content management with attached media files
- Form submissions with file attachments

## What Changes
- Add `FILE` case to `FlexyFieldType` enum
- Extend type detection in `Flexy` trait to handle `Illuminate\Http\UploadedFile` instances
- Implement file storage using Laravel's Storage facade
- Store file paths in `value_string` column (reusing existing column)
- Add file deletion handling when field values are deleted or models are deleted
- Support file-specific validation rules (mimes, max, etc.)
- Update pivot view generation to include FILE fields
- Add configuration for default file storage disk and path

## Impact
- **Affected specs:**
  - `type-system`: Add FILE type support
  - `dynamic-field-storage`: Add file storage and cleanup logic
  - `field-validation`: Add file-specific validation support
- **Affected code:**
  - `src/Enums/FlexyFieldType.php`: Add FILE case
  - `src/Traits/Flexy.php`: Add file handling in type detection and storage
  - `src/FlexyField.php`: Update pivot view to handle FILE fields
  - `config/flexyfield.php`: Add file storage configuration
  - `src/Models/Value.php`: May need file cleanup logic
- **Breaking changes:** None (additive feature)
- **Database changes:** None (reuses existing `value_string` column)

