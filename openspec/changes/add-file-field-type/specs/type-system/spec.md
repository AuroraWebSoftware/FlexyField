## ADDED Requirements

### Requirement: File Type Support
The system SHALL support FILE field type for storing file uploads via the FlexyFieldType enum, with files stored in Laravel's Storage system and file paths stored in the value_string column.

#### Scenario: File type is supported
- **WHEN** a FILE field type is defined in a field set
- **THEN** it SHALL be stored as FlexyFieldType::FILE in the ff_set_fields table
- **AND** the field_type SHALL be 'file'

#### Scenario: UploadedFile instance is stored as file
- **WHEN** an Illuminate\Http\UploadedFile instance is assigned to a FILE flexy field
- **THEN** the file SHALL be stored using Laravel's Storage facade
- **AND** the file path SHALL be stored in the value_string column
- **AND** the file SHALL be retrievable via Storage URL or path

#### Scenario: File path is stored in value_string column
- **WHEN** a file is uploaded to a FILE flexy field
- **THEN** the storage path SHALL be stored in the value_string column
- **AND** all other typed columns (value_int, value_decimal, etc.) SHALL be NULL
- **AND** the path SHALL be relative to the storage disk root

#### Scenario: File type detection handles UploadedFile
- **WHEN** an UploadedFile instance is assigned to a flexy field
- **AND** the field type is FILE
- **THEN** the system SHALL detect it as a file upload
- **AND** it SHALL NOT be treated as a string or other type

#### Scenario: File retrieval returns Storage URL or path
- **WHEN** a FILE flexy field value is retrieved
- **THEN** it SHALL return a string containing the Storage URL (if available) or file path
- **AND** it SHALL NOT return an UploadedFile instance

