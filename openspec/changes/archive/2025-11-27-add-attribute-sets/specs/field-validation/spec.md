# Field Validation

## Purpose
FlexyField provides a field-set-based validation system where field definitions include Laravel validation rules and custom error messages. Validation is performed before values are persisted to the database, ensuring data integrity for flexy fields within their assigned field sets.

## MODIFIED Requirements

### Requirement: Field Set Definition
The system SHALL allow defining fields within field sets that specify type, validation rules, and sort order.

#### Scenario: Field is defined with validation rules in field set
- **WHEN** addFieldToSet() is called with validation_rules parameter
- **THEN** a SetField record SHALL be created in ff_set_fields table
- **AND** the field SHALL include field_name, field_type, validation_rules, and validation_messages
- **AND** the field SHALL be scoped to the field set

#### Scenario: Field type matches FlexyFieldType enum
- **WHEN** a field is defined with a field_type in a field set
- **THEN** the field_type SHALL be one of the FlexyFieldType enum values
- **AND** the type SHALL be stored in ff_set_fields table

#### Scenario: Fields can be removed from field set
- **WHEN** removeFieldFromSet() is called for a field
- **THEN** the corresponding SetField record SHALL be removed from ff_set_fields
- **AND** existing values SHALL remain in ff_values

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
- **AND** the value SHALL be persisted to ff_values with field_set_code

### Requirement: Custom Validation Messages
The system SHALL support custom error messages for validation failures.

#### Scenario: Custom messages are used when provided
- **WHEN** a SetField has validation_messages defined
- **AND** validation fails for that field
- **THEN** the custom error message SHALL be used
- **AND** Laravel's default message SHALL NOT be shown

### Requirement: Validation Edge Cases
The system SHALL handle validation edge cases with clear error messages.

#### Scenario: Validation rules conflict with field type
- **WHEN** a field is defined with type INTEGER
- **AND** validation_rules contains 'email'
- **AND** a value is set that is numeric but not a valid email
- **THEN** Laravel validation SHALL fail
- **AND** a ValidationException SHALL be thrown

#### Scenario: Field value is null when required
- **WHEN** a field has validation_rules='required'
- **AND** the field value is set to null
- **THEN** Laravel validation SHALL fail
- **AND** ValidationException SHALL be thrown with "field is required" message

#### Scenario: Field value is empty string when required
- **WHEN** a field has validation_rules='required'
- **AND** the field value is set to empty string ''
- **THEN** Laravel validation SHALL fail
- **AND** ValidationException SHALL be thrown

#### Scenario: Very long validation rule string
- **WHEN** addFieldToSet() is called with validation_rules exceeding 500 characters
- **THEN** an exception SHALL be thrown
- **AND** the field SHALL NOT be created

#### Scenario: Validation messages with special characters
- **WHEN** validation_messages contains quotes or special characters
- **THEN** the messages SHALL be JSON encoded correctly
- **AND** the messages SHALL be decoded and displayed correctly when validation fails

## ADDED Requirements

### Requirement: Field Set Field Enforcement
The system SHALL enforce that only fields defined in the instance's field set can be modified.

#### Scenario: Field not in assigned field set is rejected
- **WHEN** a model instance is assigned to a field set
- **AND** a flexy field is set that is not defined in the field set
- **THEN** FieldNotInSetException SHALL be thrown
- **AND** the field SHALL NOT be saved

#### Scenario: Unassigned instance cannot set fields
- **WHEN** a model instance has no field_set_code assigned
- **AND** a flexy field is set
- **THEN** FieldSetNotFoundException SHALL be thrown
- **AND** the field SHALL NOT be saved
