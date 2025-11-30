# Change: Add File Field Type

## Why
Users need to store file uploads (documents, images, PDFs, etc.) as flexy fields. Currently, the system only supports primitive types (STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON) and cannot handle file uploads. Adding a FILE field type will enable use cases such as:
- Product catalogs with downloadable documents
- User profiles with avatar images
- Content management with attached media files
- Form submissions with file attachments

## What Changes
- **Enum Update:** Add `FILE` case to `FlexyFieldType` enum.
- **New Service:** Create `AuroraWebSoftware\FlexyField\Services\FileHandler` to encapsulate all file storage logic (upload, delete, URL generation). This prevents bloating the `Flexy` trait.
- **Trait Update:** Extend `Flexy` trait to delegate file operations to `FileHandler` when detecting `Illuminate\Http\UploadedFile` instances.
- **Storage Strategy:**
    - Store file paths in `value_string` column.
    - Support default configuration in `config/flexyfield.php`.
    - **Per-Field Configuration:** Allow overriding `disk` and `path` via schema field metadata.
- **Accessors:** Implement a helper or magic method (e.g., `getFlexyFileUrl('field_name')`) to easily retrieve the full URL of the file.
- **Cleanup:** Implement robust cleanup logic in `FileHandler` to delete files from storage when the corresponding field value or model is deleted.
- **Validation:** Support file-specific validation rules (mimes, max size) in `SchemaField`.
- **Pivot View:** Update pivot view generation to include FILE fields (returning the path).

## Impact
- **Affected specs:**
  - `type-system`: Add FILE type support
  - `dynamic-field-storage`: Add file storage and cleanup logic
  - `field-validation`: Add file-specific validation support
- **Affected code:**
  - `src/Enums/FlexyFieldType.php`: Add FILE case
  - `src/Services/FileHandler.php`: **[NEW]** Service class for storage operations
  - `src/Traits/Flexy.php`: Integrate `FileHandler`
  - `src/FlexyField.php`: Update pivot view logic
  - `config/flexyfield.php`: Add file storage configuration
  - `src/Models/Value.php`: Add model events for file cleanup
- **Breaking changes:** None (additive feature)
- **Database changes:** None (reuses existing `value_string` column)
