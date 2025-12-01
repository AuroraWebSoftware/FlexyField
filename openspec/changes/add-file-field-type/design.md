# Design: File Field Type Support

## Goal
Enable storing file uploads (documents, images, etc.) directly in FlexyFields, managing storage, retrieval, and cleanup automatically.

## Technical Approach

### 1. Enum Update
- Add `FILE` case to `FlexyFieldType` enum.

### 2. Storage Strategy
- **Database:** Store the relative file path in `value_string` column of `ff_field_values` table.
- **File System:** Use Laravel's Storage facade.
- **Configuration:**
    - Default disk and path in `config/flexyfield.php`.
    - Per-field override via `metadata` (`disk`, `path`).

### 3. Service Layer: `FileHandler`
Create `AuroraWebSoftware\FlexyField\Services\FileHandler` to encapsulate logic:
- `upload(UploadedFile $file, string $disk, string $path): string` (Returns path)
- `delete(string $path, string $disk): bool`
- `getUrl(string $path, string $disk): string`

### 4. Flexy Trait Integration
- **Setter (`__set`):**
    - Detect if value is `Illuminate\Http\UploadedFile`.
    - If yes, delegate to `FileHandler::upload`.
    - Store returned path in `value_string`.
    - If updating existing file, delete old file first.
- **Getter (`__get`):**
    - Return the path string (raw value).
    - *Decision:* Should we return a `FileValue` object or just the path?
        - *Approach:* Return path string by default to keep it simple.
        - Add helper `getFlexyFileUrl($field)` to get full URL.

### 5. Cleanup Logic
- **Model Deletion:** Listen to `deleted` event in `Flexy` trait.
    - Iterate over all FILE type fields.
    - Call `FileHandler::delete` for each.
- **Field Update:** When a file field is updated, delete the old file.

### 6. Validation
- Support standard Laravel file validation rules in `SchemaField` validation rules (e.g., `mimes:jpg,pdf`, `max:2048`).
- These rules are already supported by `Validator`, we just need to ensure they are applied to the `UploadedFile` instance before storage.

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
