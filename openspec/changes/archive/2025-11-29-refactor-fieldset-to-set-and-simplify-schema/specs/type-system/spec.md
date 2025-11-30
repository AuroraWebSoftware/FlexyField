## MODIFIED Requirements
### Requirement: Supported Field Types
The system SHALL support multiple data types for flexy fields via the FlexyFieldType enum, with type safety enforced through schema definitions.

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
- **AND** it SHALL maintain precision

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
- **AND** the value SHALL be retrievable as a JSON string or decoded array

## MODIFIED Requirements
### Requirement: Type Detection and Storage
The system SHALL correctly detect and store PHP values in their appropriate typed columns, maintaining type fidelity through the save/retrieve cycle.

#### Scenario: Boolean false is stored and retrieved correctly
- **WHEN** a flexy field is set to boolean false
- **THEN** it SHALL be stored in value_boolean column
- **AND** it SHALL be retrieved as boolean false (not integer 0)

#### Scenario: Boolean true is stored correctly
- **WHEN** a flexy field is set to boolean true
- **THEN** it SHALL be stored in value_boolean column
- **AND** it SHALL be retrieved as boolean true (not integer 1)

#### Scenario: Integer zero is stored correctly
- **WHEN** a flexy field is set to integer 0
- **THEN** it SHALL be stored in value_int column
- **AND** it SHALL be retrieved as integer 0 (not boolean false)

#### Scenario: Float values are stored as decimal
- **WHEN** a flexy field is set to a float value (e.g., 19.99)
- **THEN** it SHALL be stored in value_decimal column
- **AND** it SHALL maintain precision when retrieved

#### Scenario: Numeric strings preserve leading zeros
- **WHEN** a flexy field is set to a numeric string with leading zeros (e.g., "00123")
- **THEN** it SHALL be stored in value_string column
- **AND** it SHALL preserve the exact string value including leading zeros

#### Scenario: Unsupported types throw exception
- **WHEN** a flexy field is set to an unsupported type (e.g., resource, closure)
- **THEN** it SHALL throw FlexyFieldTypeNotAllowedException
- **AND** the exception message SHALL include the actual type name

#### Scenario: Null values are handled correctly
- **WHEN** a flexy field is set to null
- **THEN** all value_* columns SHALL be NULL
- **AND** the value SHALL be retrieved as null

