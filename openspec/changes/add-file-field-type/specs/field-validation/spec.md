## ADDED Requirements

### Requirement: File Validation Rules
The system SHALL support Laravel file validation rules for FILE field types, including mime type, file size, and other file-specific validations.

#### Scenario: File validation rules are enforced
- **WHEN** a FILE flexy field has validation_rules defined in its SetField
- **AND** a file is uploaded to that field
- **THEN** Laravel file validation SHALL be performed using the defined rules
- **AND** common file validation rules (mimes, max, image, etc.) SHALL be supported

#### Scenario: Invalid file types are rejected
- **WHEN** a FILE flexy field has 'mimes:pdf,doc,docx' validation rule
- **AND** a file with unsupported mime type is uploaded
- **THEN** ValidationException SHALL be thrown
- **AND** the file SHALL NOT be stored
- **AND** the model save operation SHALL be aborted

#### Scenario: File size validation is enforced
- **WHEN** a FILE flexy field has 'max:2048' validation rule (2MB limit)
- **AND** a file larger than 2MB is uploaded
- **THEN** ValidationException SHALL be thrown
- **AND** the file SHALL NOT be stored
- **AND** the validation error message SHALL indicate the size limit

#### Scenario: Image validation rules work for FILE fields
- **WHEN** a FILE flexy field has 'image' or 'mimes:jpeg,png,gif' validation rule
- **AND** an image file is uploaded
- **THEN** the validation SHALL pass if the file is a valid image
- **AND** non-image files SHALL be rejected with ValidationException

#### Scenario: File validation messages are customizable
- **WHEN** a FILE flexy field has custom validation_messages defined
- **AND** file validation fails
- **THEN** the custom error message SHALL be displayed
- **AND** the default Laravel validation message SHALL NOT be shown

#### Scenario: Multiple file validation rules are supported
- **WHEN** a FILE flexy field has multiple validation rules (e.g., 'required|mimes:pdf|max:5120')
- **AND** a file is uploaded
- **THEN** all validation rules SHALL be checked
- **AND** if any rule fails, ValidationException SHALL be thrown with appropriate message

