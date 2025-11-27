# Type System Spec Changes

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
- **WHEN** a flexy field is set to an unsupported type (e.g., resource)
- **THEN** it SHALL throw FlexyFieldTypeNotAllowedException
- **AND** the exception message SHALL include the actual type name
