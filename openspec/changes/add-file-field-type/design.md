# Design: File Field Type Support

## Goal
Enable storing file uploads (documents, images, etc.) directly in FlexyFields, managing storage, retrieval, and cleanup automatically.

## Technical Approach

### 1. Enum Update
- Add `FILE` case to `FlexyFieldType` enum.

### 2. Security-First Storage Strategy
- **Database:** Store the relative file path in `value_string` column of `ff_field_values` table.
- **File System:** Use Laravel's Storage facade with security validations.
- **Configuration:**
    - Default disk and path in `config/flexyfield.php`.
    - Per-field override via `metadata` (`disk`, `path`, `max_size`, `allowed_extensions`, `allowed_mimes`).
    - Path traversal protection and secure path generation.

### 3. Security-Enhanced Service Layer: `FileHandler`
Create `AuroraWebSoftware\FlexyField\Services\FileHandler` with comprehensive security:

**Core Methods:**
- `upload(UploadedFile $file, string $disk, string $path, array $metadata = []): string` (Returns path)
- `delete(string $path, string $disk): bool`
- `getUrl(string $path, string $disk, bool $signed = false): string`
- `exists(string $path, string $disk): bool`

**Security Validations:**
- File extension whitelist validation
- MIME type validation against allowed types
- File size limits enforcement
- Path traversal protection (`../`, `./`, etc.)
- Filename sanitization and uniqueness
- Upload integrity checks

**Error Handling:**
- Comprehensive exception handling with specific error messages
- Transaction safety with rollback support
- Detailed logging for security events

### 4. Advanced Path Management
- **Hierarchical Path Structure:** `{model_type}/{schema_code}/{field_name}/{year}/{month}/{filename}`
- **Unique Filename Generation:** Prevent conflicts and security issues
- **Original Name Preservation:** Optional feature for user-facing filenames
- **CDN Support:** Optional CDN URL configuration for performance

### 5. Flexy Trait Integration
- **Setter (`__set`):**
    - Detect if value is `Illuminate\Http\UploadedFile`.
    - If yes, delegate to `FileHandler::upload` with security validations.
    - Store returned path in `value_string`.
    - If updating existing file, delete old file first (with error handling).
    - Support array uploads for bulk operations.
- **Getter (`__get`):**
    - Return the path string (raw value).
    - Add helper `getFlexyFileUrl($field, $signed = false)` to get full URL.
    - Add `getFlexyFileUrlSigned($field, $expiresAt)` for temporary URLs.
    - Add `flexyFileExists($field)` to check file existence.

### 6. Comprehensive Cleanup Logic
- **Model Deletion:** Listen to `deleted` event in `Flexy` trait.
    - Iterate over all FILE type fields.
    - Call `FileHandler::delete` for each with error handling.
    - Bulk deletion support for performance.
- **Field Update:** When a file field is updated, delete the old file first.
- **Failed Upload Cleanup:** Clean up temporary files on upload failures.
- **Orphan File Detection:** Periodic cleanup of orphaned files.

### 7. Advanced Validation System
- **Laravel Validation Integration:** Standard Laravel file validation rules (e.g., `mimes:jpg,pdf`, `max:2048`).
- **Security-Focused Validation:**
    - Extension whitelist enforcement
    - MIME type validation
    - File size limits
    - Malicious file detection (basic)
    - Image validation for image files
- **Metadata-Based Validation:** Per-field validation configuration.
- **Custom Validation Rules:** Support for custom validation logic.

### 8. Transaction Safety
- **Two-Phase Upload:** Temporary storage → Database save → Final location move.
- **Rollback Support:** Automatic cleanup on transaction failure.
- **Atomic Operations:** Ensure database and file system consistency.
- **Error Recovery:** Graceful handling of partial failures.

### 9. Performance Optimizations
- **Bulk Operations:** Support for bulk file uploads and deletions.
- **Lazy Loading:** File URLs generated only when needed.
- **Caching:** File existence and metadata caching.
- **CDN Integration:** Automatic CDN URL generation for static files.
- **Image Optimization:** Optional image resizing and optimization.

### 10. Monitoring and Logging
- **Security Event Logging:** Track all file operations for security monitoring.
- **Performance Monitoring:** Log slow operations and resource usage.
- **Error Tracking:** Comprehensive error logging with context.
- **Audit Trail:** Track who uploaded what files when (if needed).

## Architecture
- **Enum:** `FlexyFieldType::FILE`
- **Service:** `FileHandler` (New)
- **Trait:** `Flexy` (Update set/get/delete logic)
- **Config:** `flexyfield.php` (New config file)

## Edge Cases
- **Transaction Failure:** If DB save fails after file upload, file remains orphan.
    - *Mitigation:* Acceptable risk for MVP. strict implementation would use temporary storage and move on commit.
- **Disk Change:** If disk config changes, old paths might be invalid.
    - *Constraint:* Changing disk for existing fields is a breaking change for data.
