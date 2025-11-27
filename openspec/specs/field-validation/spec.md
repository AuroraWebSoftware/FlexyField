# Field Validation

## Purpose
FlexyField provides a Shape-based validation system where field definitions include Laravel validation rules and custom error messages. Validation is performed before values are persisted to the database, ensuring data integrity for flexy fields.
## Requirements
### Requirement: Shape Definition
The system SHALL allow defining field shapes that specify type, validation rules, and sort order.

#### Scenario: Shape is defined with validation rules
- **WHEN** setFlexyShape() is called on a model
- **THEN** a Shape record SHALL be created in ff_shapes table
- **AND** the shape SHALL include field_name, field_type, sort_order, validation_rule, and validation_messages

#### Scenario: Shape type matches FlexyFieldType enum
- **WHEN** a shape is defined with a field_type
- **THEN** the field_type SHALL be one of the FlexyFieldType enum values
- **AND** the type SHALL be stored in the ff_shapes table

#### Scenario: Shapes can be deleted
- **WHEN** deleteFlexyShape() is called for a field
- **THEN** the corresponding Shape record SHALL be removed from ff_shapes
- **AND** existing values SHALL remain in ff_values

### Requirement: Validation Rule Enforcement
The system SHALL validate flexy field values against their Shape validation rules before saving.

#### Scenario: Values are validated when Shape exists
- **WHEN** a flexy field has a defined Shape
- **AND** the field value is modified
- **THEN** Laravel validation SHALL be performed using the Shape's validation_rule
- **AND** the field value SHALL be validated before being saved

#### Scenario: Invalid values are rejected
- **WHEN** a flexy field value fails validation
- **THEN** a ValidationException SHALL be thrown
- **AND** the model save operation SHALL be aborted
- **AND** no changes SHALL be persisted to ff_values

#### Scenario: Valid values pass validation
- **WHEN** a flexy field value passes validation
- **THEN** no exception SHALL be thrown
- **AND** the value SHALL be persisted to ff_values

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

### Requirement: Validation with Shapes Optional
The system SHALL allow flexy fields to be used without predefined Shapes.

#### Scenario: Fields without Shapes are not validated
- **WHEN** a flexy field does not have a defined Shape
- **AND** the field value is modified
- **THEN** no validation SHALL be performed
- **AND** the value SHALL be saved directly based on type detection

#### Scenario: Shape enforcement can be enabled per model
- **WHEN** a model sets $hasShape = true
- **THEN** only fields with defined Shapes SHALL be allowed
- **AND** attempting to set undefined fields SHALL throw FlexyFieldIsNotInShape exception

### Requirement: Field Existence Validation
The system SHALL enforce Shape requirements when strict mode is enabled.

#### Scenario: Strict mode requires Shape for all fields
- **WHEN** ExampleModel::$hasShape is true
- **AND** a flexy field is set without a corresponding Shape
- **THEN** FlexyFieldIsNotInShape exception SHALL be thrown
- **AND** the field SHALL NOT be saved

#### Scenario: Non-strict mode allows ad-hoc fields
- **WHEN** ExampleModel::$hasShape is false or not set
- **AND** a flexy field is set without a corresponding Shape
- **THEN** no exception SHALL be thrown
- **AND** the field SHALL be saved using type detection

