# Field Validation Spec Changes

## MODIFIED Requirements

### Requirement: Custom Validation Messages
The system SHALL support custom validation error messages defined in Shape validation_messages property.

#### Scenario: Custom validation messages are displayed
- **WHEN** a Shape has custom validation_messages defined
- **AND** validation fails for that field
- **THEN** the custom error message SHALL be displayed to the user
- **AND** the default Laravel validation message SHALL NOT be shown

#### Scenario: Validation messages property is correctly accessed
- **WHEN** the system validates a field with a Shape
- **THEN** it SHALL read from the validation_messages property
- **AND** it SHALL NOT attempt to read from validation_rule property
