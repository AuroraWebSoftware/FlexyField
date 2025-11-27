# Type System

## Purpose
FlexyField provides a strongly-typed storage system for dynamic fields. Each value is stored in a type-specific column based on automatic type detection or explicit Shape definitions. Supported types include STRING, INTEGER, DECIMAL, DATE, DATETIME, BOOLEAN, and JSON.

## Requirements

### Requirement: Supported Field Types
The system SHALL support multiple data types for flexy fields via the FlexyFieldType enum.

#### Scenario: String type is supported
- **WHEN** a string value is assigned to a flexy field
- **THEN** it SHALL be stored in the value_string column
- **AND** the value SHALL be retrieved as a string

#### Scenario: Integer type is supported
- **WHEN** an integer value is assigned to a flexy field
- **THEN** it SHALL be stored in the value_int column
- **AND** the value SHALL be retrieved as an integer

#### Scenario: Decimal type is supported
- **WHEN** a decimal/float value is assigned to a flexy field
- **THEN** it SHALL be stored in the value_decimal column
- **AND** the value SHALL maintain precision

#### Scenario: Boolean type is supported
- **WHEN** a boolean value is assigned to a flexy field
- **THEN** it SHALL be stored in the value_boolean column
- **AND** the value SHALL be retrieved as a boolean

#### Scenario: Date type is supported
- **WHEN** a date value is assigned to a flexy field
- **THEN** it SHALL be stored in the value_datetime column
- **AND** the value SHALL be retrieved as a Carbon instance

#### Scenario: DateTime type is supported
- **WHEN** a datetime value is assigned to a flexy field
- **THEN** it SHALL be stored in the value_datetime column
- **AND** the value SHALL be retrieved as a Carbon instance with time

#### Scenario: JSON type is supported
- **WHEN** an array is assigned to a flexy field
- **THEN** it SHALL be JSON encoded and stored in the value_json column
- **AND** the value SHALL be retrievable as a JSON string

### Requirement: Type Detection and Storage
The system SHALL automatically detect the PHP type of assigned values and store them in the appropriate column.

#### Scenario: Type is detected from value
- **WHEN** a value is assigned without an explicit Shape
- **THEN** the system SHALL detect the PHP type using is_* functions
- **AND** the value SHALL be stored in the corresponding typed column

#### Scenario: DateTime instances are detected
- **WHEN** a DateTime or Carbon instance is assigned
- **THEN** it SHALL be stored in value_datetime column
- **AND** no other type detection SHALL occur for DateTime instances

#### Scenario: Arrays are converted to JSON
- **WHEN** an array value is assigned
- **THEN** it SHALL be detected as array type
- **AND** it SHALL be JSON encoded before storage
- **AND** it SHALL be stored in value_json column

### Requirement: Type Safety in Storage
The system SHALL ensure that only one typed column is populated per value record.

#### Scenario: Only one typed column is populated
- **WHEN** a value is stored
- **THEN** exactly one value_* column SHALL contain data
- **AND** all other typed columns SHALL be NULL

#### Scenario: String values default to value_string
- **WHEN** type cannot be determined definitively
- **THEN** the value SHALL be stored as a string
- **AND** it SHALL use the value_string column
