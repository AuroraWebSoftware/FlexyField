# Change: Add File Field Type

## Why
Users need to store file uploads (documents, images, PDFs, etc.) as flexy fields. Currently, the system only supports primitive types (STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, JSON) and cannot handle file uploads. Adding a FILE field type will enable use cases such as:
- Product catalogs with downloadable documents
- User profiles with avatar images
- Content management with attached media files
- Form submissions with file attachments

## What Changes
- **Enum Update:** Add `FILE` case to `FlexyFieldType` enum.
- **Security-Enhanced Service:** Create `AuroraWebSoftware\FlexyField\Services\FileHandler` with comprehensive security validations, transaction safety, and advanced error handling. This prevents bloating the `Flexy` trait while ensuring production-ready security.
- **Trait Update:** Extend `Flexy` trait to delegate file operations to `FileHandler` when detecting `Illuminate\Http\UploadedFile` instances, with support for bulk operations and error recovery.
- **Advanced Storage Strategy:**
    - Store file paths in `value_string` column with security validations.
    - Support default configuration in `config/flexyfield.php` with security defaults.
    - **Per-Field Configuration:** Allow overriding `disk`, `path`, `max_size`, `allowed_extensions`, `allowed_mimes` via schema field metadata.
    - **Hierarchical Path Structure:** Organize files by model type, schema, field, date.
    - **Unique Filename Generation:** Prevent conflicts and security issues.
- **Enhanced Accessors:** 
    - `getFlexyFileUrl('field_name', $signed = false)` - Get file URL with optional signed URLs.
    - `getFlexyFileUrlSigned('field_name', $expiresAt)` - Get temporary signed URL.
    - `flexyFileExists('field_name')` - Check if file exists.
    - Bulk file operations support.
- **Comprehensive Cleanup:** Robust cleanup logic with error handling, orphan file detection, and bulk operations.
- **Advanced Validation:** File-specific validation rules (mimes, max size) with security-focused extensions and MIME type validation.
- **Pivot View:** Update pivot view generation to include FILE fields (returning the path).
- **Transaction Safety:** Two-phase upload process with rollback support to prevent orphan files.
- **Performance Optimizations:** Bulk operations, lazy loading, CDN support, and image optimization.
- **Monitoring & Logging:** Security event logging, performance monitoring, and audit trails.

## Security Considerations

### Critical Security Features
- **File Extension Whitelist:** Only allow predefined safe extensions
- **MIME Type Validation:** Verify actual file type matches extension
- **File Size Limits:** Enforce maximum file size restrictions
- **Path Traversal Protection:** Prevent directory traversal attacks
- **Filename Sanitization:** Clean and secure filename generation
- **Upload Integrity Checks:** Validate file uploads are legitimate
- **Transaction Safety:** Prevent orphan files and ensure consistency
- **Comprehensive Logging:** Security event tracking and audit trails

### Security Configuration
```php
// config/flexyfield.php security settings
'file_storage' => [
    'max_file_size' => 10240, // KB (10MB default)
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
    'allowed_mimes' => [
        'image/jpeg', 'image/png', 'application/pdf', 
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ],
    'path_structure' => '{model_type}/{schema_code}/{field_name}/{year}/{month}',
    'generate_unique_names' => true,
    'enable_security_logging' => true,
]
```

## Edge Cases and Error Handling

### Transaction Failure Handling
- **Problem:** Database save fails after file upload, leaving orphan files
- **Solution:** Two-phase upload with temporary storage and rollback
- **Implementation:** Upload to temp location → Save model → Move to final location
- **Cleanup:** Automatic temp file deletion on any failure

### Disk Configuration Changes
- **Problem:** Disk configuration changes invalidate existing file paths
- **Solution:** Migration tool to update paths or maintain disk mapping
- **Constraint:** Changing disk for existing fields is a breaking change

### Concurrent File Operations
- **Problem:** Multiple requests trying to upload/delete same file
- **Solution:** File locking and optimistic concurrency control
- **Implementation:** Unique filename generation prevents conflicts

### Large File Handling
- **Problem:** Very large files cause memory and timeout issues
- **Solution:** Streaming uploads and chunked processing
- **Configuration:** Configurable chunk size and timeout limits

### Storage Provider Failures
- **Problem:** Cloud storage (S3, etc.) temporarily unavailable
- **Solution:** Retry logic with exponential backoff and circuit breaker
- **Fallback:** Local storage fallback for critical operations

### Malicious File Detection
- **Problem:** Users upload malicious files disguised as safe types
- **Solution:** File signature validation and content analysis
- **Implementation:** Magic number checking and basic malware scanning

### Orphan File Cleanup
- **Problem:** Files remain after model deletion or failed uploads
- **Solution:** Scheduled cleanup tasks and reference checking
- **Implementation:** Laravel Scheduler task for periodic cleanup

## Impact
- **Affected specs:**
  - `type-system`: Add FILE type support with security validations
  - `dynamic-field-storage`: Add file storage, cleanup logic, and transaction safety
  - `field-validation`: Add file-specific validation with security focus
  - `query-integration`: File URL generation and bulk operations
- **Affected code:**
  - `src/Enums/FlexyFieldType.php`: Add FILE case
  - `src/Services/FileHandler.php`: **[NEW]** Security-enhanced service class with comprehensive file operations
  - `src/Traits/Flexy.php`: Integrate `FileHandler` with bulk operations and error handling
  - `src/FlexyField.php`: Update pivot view logic for FILE fields
  - `config/flexyfield.php`: Add comprehensive file storage configuration with security defaults
  - `src/Models/Value.php`: Add model events for file cleanup with error handling
  - `src/Exceptions/FileException.php`: **[NEW]** Custom exception handling for file operations
- **Breaking changes:** None (additive feature)
- **Database changes:** None (reuses existing `value_string` column)
- **Security impact:** Significantly enhanced security posture with comprehensive validations
- **Performance impact:** Optimized with bulk operations and caching, minimal overhead
- **Monitoring impact:** Added security logging and performance monitoring capabilities
