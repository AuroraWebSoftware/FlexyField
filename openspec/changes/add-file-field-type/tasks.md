## 1. Core Type System
- [ ] 1.1 Add FILE case to FlexyFieldType enum
- [ ] 1.2 Update type detection in Flexy trait to recognize UploadedFile instances
- [ ] 1.3 Add file storage logic in Flexy trait's saving event handler
- [ ] 1.4 Update type detection order to handle UploadedFile before string fallback

## 2. File Storage Implementation
- [ ] 2.1 Add file storage configuration to config/flexyfield.php (disk, path)
- [ ] 2.2 Implement file storage method in Flexy trait (store file, return path)
- [ ] 2.3 Implement file deletion method in Flexy trait
- [ ] 2.4 Add file cleanup on value deletion (when field value is removed)
- [ ] 2.5 Add file cleanup on model deletion (in deleted event handler)
- [ ] 2.6 Handle file replacement (delete old file when new file uploaded)

## 3. File Retrieval
- [ ] 3.1 Update value retrieval to return Storage URL for FILE fields
- [ ] 3.2 Ensure FILE fields return string paths/URLs (not UploadedFile instances)
- [ ] 3.3 Handle missing files gracefully (return null or path if file doesn't exist)

## 4. Pivot View Integration
- [ ] 4.1 Update MySQL pivot view generation to include FILE fields (as strings)
- [ ] 4.2 Update PostgreSQL pivot view generation to include FILE fields (as strings)
- [ ] 4.3 Test pivot view includes FILE field columns correctly

## 5. Validation Support
- [ ] 5.1 Ensure file validation rules work (mimes, max, etc.)
- [ ] 5.2 Test validation with common file types (images, PDFs, documents)
- [ ] 5.3 Test validation with file size limits

## 6. Testing
- [ ] 6.1 Unit test: FILE type enum case exists
- [ ] 6.2 Unit test: UploadedFile detection and storage
- [ ] 6.3 Unit test: File path stored in value_string column
- [ ] 6.4 Unit test: File deletion on value removal
- [ ] 6.5 Unit test: File deletion on model deletion
- [ ] 6.6 Unit test: File replacement (old file deleted)
- [ ] 6.7 Feature test: Complete file upload workflow
- [ ] 6.8 Feature test: File validation rules
- [ ] 6.9 Feature test: FILE field in pivot view queries
- [ ] 6.10 Feature test: Storage URL retrieval
- [ ] 6.11 Edge case test: Missing file handling
- [ ] 6.12 Edge case test: Storage disk unavailable

## 7. Documentation
- [ ] 7.1 Update README with FILE field type usage examples
- [ ] 7.2 Document file storage configuration options
- [ ] 7.3 Document file validation rules and best practices
- [ ] 7.4 Add examples of file upload handling in controllers

