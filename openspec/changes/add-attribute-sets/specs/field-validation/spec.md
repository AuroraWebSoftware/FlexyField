# Field Validation

## Purpose
FlexyField provides an attribute-set-based validation system where field definitions include Laravel validation rules and custom error messages. Validation is performed before values are persisted to the database, ensuring data integrity for flexy fields within their assigned attribute sets.

## MODIFIED Requirements

### Requirement: Field Definition with Validation Rules
The system SHALL allow defining fields with validation rules within attribute sets.

#### Scenario: Field is defined with validation rules in attribute set
- **WHEN** addFieldToSet() is called with validation_rules parameter
- **THEN** an AttributeSetField record SHALL be created in ff_attribute_set_fields table
- **AND** the field SHALL include field_name, field_type, validation_rules, and validation_messages
- **AND** the field SHALL be scoped to the attribute set

#### Scenario: Field type matches FlexyFieldType enum
- **WHEN** a field is defined with a field_type in an attribute set
- **THEN** the field_type SHALL be one of the FlexyFieldType enum values
- **AND** the type SHALL be stored in ff_attribute_set_fields table

#### Scenario: Fields can be removed from attribute set
- **WHEN** removeFieldFromSet() is called for a field
- **THEN** the corresponding AttributeSetField record SHALL be removed from ff_attribute_set_fields
- **AND** existing values SHALL remain in ff_values

### Requirement: Validation Rule Enforcement
The system SHALL validate flexy field values against their AttributeSetField validation rules before saving.

#### Scenario: Values are validated when field exists in assigned set
- **WHEN** a flexy field has a defined AttributeSetField in the instance's attribute set
- **AND** the field value is modified
- **THEN** Laravel validation SHALL be performed using the field's validation_rules
- **AND** the field value SHALL be validated before being saved

#### Scenario: Invalid values are rejected
- **WHEN** a flexy field value fails validation
- **THEN** a ValidationException SHALL be thrown
- **AND** the model save operation SHALL be aborted
- **AND** no changes SHALL be persisted to ff_values

#### Scenario: Valid values pass validation
- **WHEN** a flexy field value passes validation
- **THEN** no exception SHALL be thrown
- **AND** the value SHALL be persisted to ff_values with attribute_set_code

### Requirement: Custom Validation Messages
The system SHALL support custom error messages for validation failures.

#### Scenario: Custom messages are used when provided
- **WHEN** an AttributeSetField has validation_messages defined
- **AND** validation fails for that field
- **THEN** the custom error message SHALL be used
- **AND** Laravel's default message SHALL NOT be shown

## REMOVED Requirements

### Requirement: Validation with Shapes Optional
This requirement is removed. All fields must be defined in attribute sets.

### Requirement: Field Existence Validation
This requirement is removed and replaced by attribute set field enforcement.

## ADDED Requirements

### Requirement: Attribute Set Field Enforcement
The system SHALL enforce that only fields defined in the instance's attribute set can be modified.

#### Scenario: Field not in assigned attribute set is rejected
- **WHEN** a model instance is assigned to an attribute set
- **AND** a flexy field is set that is not defined in the attribute set
- **THEN** FieldNotInAttributeSetException SHALL be thrown
- **AND** the field SHALL NOT be saved

#### Scenario: Unassigned instance cannot set fields
- **WHEN** a model instance has no attribute_set_code assigned
- **AND** a flexy field is set
- **THEN** AttributeSetNotFoundException SHALL be thrown
- **AND** the field SHALL NOT be saved
