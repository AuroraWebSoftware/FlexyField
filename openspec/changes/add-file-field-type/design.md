# Design: File Field Type Implementation

## Context
FlexyField currently supports primitive data types stored directly in database columns. Files require a different approach: they must be stored in the filesystem (via Laravel Storage) while only storing the file path/identifier in the database.

## Goals / Non-Goals

### Goals
- Support file uploads as a first-class field type
- Integrate seamlessly with Laravel's Storage system
- Automatically clean up files when field values are deleted
- Support standard Laravel file validation rules
- Maintain backward compatibility with existing field types

### Non-Goals
- File versioning or history tracking
- Direct file streaming through FlexyField
- File transformation/resizing (can be handled by application layer)
- Multiple file storage per field (single file per field)

## Decisions

### Decision: Store File Path in value_string Column
**Rationale:** Reuse existing `value_string` column instead of adding a new `value_file` column. This keeps the schema simple and follows the pattern of storing identifiers/paths as strings.

**Alternatives considered:**
- New `value_file` column: Adds unnecessary schema complexity
- Store in `value_json`: Overkill for a simple path string

### Decision: Use Laravel Storage Facade
**Rationale:** Leverage Laravel's built-in storage abstraction for consistency with Laravel applications. Supports multiple disk drivers (local, S3, etc.) without code changes.

**Alternatives considered:**
- Direct filesystem operations: Less flexible, harder to test
- Custom storage layer: Unnecessary abstraction

### Decision: Store Full Storage Path
**Rationale:** Store the full path relative to the storage disk root (e.g., `flexyfield/products/document.pdf`) to enable easy retrieval and deletion.

**Alternatives considered:**
- Store only filename: Requires additional logic to reconstruct path
- Store absolute path: Breaks portability between environments

### Decision: Automatic File Cleanup on Value Deletion
**Rationale:** Prevent orphaned files in storage. When a field value is deleted or a model is deleted, automatically remove the associated file.

**Alternatives considered:**
- Manual cleanup: Risk of storage bloat
- Soft delete with cleanup job: Adds complexity without clear benefit

### Decision: Default Storage Path Structure
**Rationale:** Use `flexyfield/{model_type}/{field_name}/{filename}` structure to organize files and prevent collisions.

**Example:** `flexyfield/App\Models\Product/document/product-spec.pdf`

**Alternatives considered:**
- Flat structure: Higher collision risk
- UUID-based paths: Less human-readable, harder to debug

### Decision: Accept UploadedFile Instances Only
**Rationale:** Only accept `Illuminate\Http\UploadedFile` instances to ensure proper validation and security. Reject file paths or other file representations.

**Alternatives considered:**
- Accept file paths: Security risk, bypasses validation
- Accept multiple file types: Adds complexity without clear benefit

## Risks / Trade-offs

### Risk: Storage Disk Configuration
**Mitigation:** Provide sensible defaults (local disk, `flexyfield/` path) and allow configuration override in `config/flexyfield.php`.

### Risk: File Deletion Failures
**Mitigation:** Log deletion failures but don't throw exceptions that would prevent model deletion. Consider adding a cleanup command for orphaned files.

### Risk: Large File Uploads
**Mitigation:** Rely on Laravel's validation rules (max:size) and PHP configuration (upload_max_filesize, post_max_size). Document recommended limits.

### Risk: Storage Disk Not Available
**Mitigation:** Validate disk availability during file storage and throw meaningful exceptions. Consider graceful degradation (store path even if file storage fails, with warning).

## Migration Plan

### No Database Migration Required
The implementation reuses the existing `value_string` column, so no migration is needed.

### Configuration Addition
Add file storage configuration to `config/flexyfield.php`:
- `file_storage.disk`: Default storage disk (default: 'local')
- `file_storage.path`: Base path for files (default: 'flexyfield')

### Backward Compatibility
- Existing STRING fields are unaffected
- FILE type is additive, no breaking changes
- Models without FILE fields continue to work as before

## Open Questions

1. **Should we support file retrieval as Storage URLs?** 
   - Decision: Yes, when retrieving a FILE field, return the Storage URL if available, otherwise return the path.

2. **How to handle file replacement?**
   - Decision: When a new file is uploaded for an existing FILE field, delete the old file before storing the new one.

3. **Should FILE fields be queryable in the pivot view?**
   - Decision: Yes, include FILE fields in the pivot view as string paths for consistency with other types.

