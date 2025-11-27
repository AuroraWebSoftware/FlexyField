## ADDED Requirements

### Requirement: File Storage and Cleanup
The system SHALL store uploaded files in Laravel's Storage system and automatically clean up files when field values or models are deleted.

#### Scenario: File is stored in configured storage disk
- **WHEN** a file is uploaded to a FILE flexy field
- **THEN** it SHALL be stored using the configured storage disk (default: 'local')
- **AND** it SHALL be stored in the configured base path (default: 'flexyfield/{model_type}/{field_name}/')
- **AND** the file path SHALL be stored in value_string column

#### Scenario: File deletion on value removal
- **WHEN** a FILE flexy field value is deleted or set to null
- **AND** a file path exists in value_string
- **THEN** the associated file SHALL be deleted from storage
- **AND** the value record SHALL be removed from ff_values table

#### Scenario: File deletion on model deletion
- **WHEN** a model with FILE flexy fields is deleted
- **THEN** all associated files SHALL be deleted from storage
- **AND** all value records SHALL be removed from ff_values table

#### Scenario: File replacement deletes old file
- **WHEN** a new file is uploaded to a FILE flexy field that already has a file
- **THEN** the old file SHALL be deleted from storage before storing the new file
- **AND** the new file path SHALL replace the old path in value_string

#### Scenario: File storage path structure
- **WHEN** a file is stored for a FILE flexy field
- **THEN** the path SHALL follow the pattern: {base_path}/{model_type}/{field_name}/{filename}
- **AND** the path SHALL be unique to prevent collisions
- **AND** the filename SHALL preserve the original filename or use a unique identifier

#### Scenario: Pivot view includes FILE fields
- **WHEN** the pivot view is created or recreated
- **AND** FILE fields exist in ff_values
- **THEN** FILE fields SHALL be included in the pivot view as string columns
- **AND** the columns SHALL be prefixed with flexy_
- **AND** the values SHALL be the file paths from value_string

#### Scenario: Storage disk configuration
- **WHEN** file storage configuration is provided in config/flexyfield.php
- **THEN** the system SHALL use the configured disk and path
- **AND** if configuration is missing, it SHALL use defaults (disk: 'local', path: 'flexyfield')

