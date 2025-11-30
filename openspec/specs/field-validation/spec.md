# Field Validation

## Purpose
FlexyField provides a Field Set-based validation system where field definitions include Laravel validation rules and custom error messages. Validation is performed before values are persisted to the database, ensuring data integrity for flexy fields.
## Requirements
### Requirement: Validation Rule Enforcement
The system SHALL validate flexy field values against their SetField validation rules before saving, only for fields in the assigned field set.

#### Scenario: Values are validated when SetField exists
- **WHEN** a flexy field has a defined SetField in the assigned field set
- **AND** the field value is modified
- **THEN** Laravel validation SHALL be performed using the SetField's validation_rules
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
The system SHALL support custom validation error messages defined in SetField validation_messages property.

#### Scenario: Custom validation messages are displayed
- **WHEN** a SetField has custom validation_messages defined
- **AND** validation fails for that field
- **THEN** the custom error message SHALL be displayed to the user
- **AND** the default Laravel validation message SHALL NOT be shown

#### Scenario: Validation messages property is correctly accessed
- **WHEN** the system validates a field with a SetField
- **THEN** it SHALL read from the validation_messages property
- **AND** it SHALL NOT attempt to read from validation_rules property

### Requirement: Validation Edge Cases
The system SHALL handle validation edge cases correctly.

#### Scenario: Null values are validated correctly
- **WHEN** a field has 'required' validation rule
- **AND** the field value is null
- **THEN** ValidationException SHALL be thrown

#### Scenario: Nullable fields allow null
- **WHEN** a field has 'nullable' validation rule
- **AND** the field value is null
- **THEN** no exception SHALL be thrown
- **AND** the null value SHALL be stored

#### Scenario: Empty strings are validated correctly
- **WHEN** a field has 'required' validation rule
- **AND** the field value is empty string
- **THEN** ValidationException SHALL be thrown (empty string fails 'required')

#### Scenario: Validation with special characters
- **WHEN** validation_messages contains special characters
- **THEN** the messages SHALL be properly JSON encoded and decoded
- **AND** special characters SHALL be preserved

### Requirement: Schema Definition
The system SHALL allow defining fields within field sets that specify type, validation rules, and sort order.

#### Scenario: Field is defined with validation rules in field set
- **WHEN** addFieldToSet() is called with validation_rules
- **THEN** a SetField record SHALL be created in ff_set_fields table
- **AND** the field SHALL include field_name, field_type, sort, validation_rules, and validation_messages
- **AND** the field SHALL be scoped to the specified set_code

#### Scenario: Field type matches FlexyFieldType enum
- **WHEN** a field is added to a field set with a field_type
- **THEN** the field_type SHALL be one of the FlexyFieldType enum values
- **AND** the type SHALL be stored in the ff_set_fields table

#### Scenario: Fields can be removed from field sets
- **WHEN** removeFieldFromSet() is called for a field
- **THEN** the corresponding SetField record SHALL be removed from ff_set_fields
- **AND** existing values SHALL remain in ff_values but become inaccessible for new assignments

### Requirement: Schema Enforcement
The system SHALL enforce that only fields from the assigned field set can be set.

#### Scenario: Field not in set is rejected
- **WHEN** a model has an assigned field_set_code
- **AND** a flexy field is set that is not in the assigned field set
- **THEN** FieldNotInSetException SHALL be thrown
- **AND** the field SHALL NOT be saved
- **AND** the exception message SHALL include the field name, set code, and available fields

#### Scenario: Field set assignment is required
- **WHEN** a model attempts to set a flexy field
- **AND** the model has no field_set_code assigned
- **THEN** FieldSetNotFoundException SHALL be thrown
- **AND** the field SHALL NOT be saved

### Requirement: Schema Field Enforcement
The system SHALL enforce that only fields defined in the instance's schema can be modified.

#### Scenario: Field not in assigned schema is rejected
- **WHEN** a model instance is assigned to a schema
- **AND** a flexy field is set that is not defined in the schema
- **THEN** FieldNotInSchemaException SHALL be thrown
- **AND** the field SHALL NOT be saved

#### Scenario: Unassigned instance cannot set fields
- **WHEN** a model instance has no schema_code assigned
- **AND** a flexy field is set
- **THEN** SchemaNotFoundException SHALL be thrown
- **AND** the field SHALL NOT be saved

