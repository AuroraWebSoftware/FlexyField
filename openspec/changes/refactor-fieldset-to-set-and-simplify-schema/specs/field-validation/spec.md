## MODIFIED Requirements
### Requirement: Set Definition
The system SHALL allow defining fields within sets that specify type, validation rules, and sort order.

#### Scenario: Field is defined with validation rules in set
- **WHEN** addFieldToSet() is called with validation_rules
- **THEN** a SetField record SHALL be created in ff_set_fields table
- **AND** the field SHALL include name, type, sort, validation_rules, and validation_messages
- **AND** the field SHALL be scoped to the specified code

#### Scenario: Field type matches FlexyFieldType enum
- **WHEN** a field is added to a set with a type
- **THEN** the type SHALL be one of the FlexyFieldType enum values
- **AND** the type SHALL be stored in the ff_set_fields table

#### Scenario: Fields can be removed from sets
- **WHEN** removeFieldFromSet() is called for a field
- **THEN** the corresponding SetField record SHALL be removed from ff_set_fields
- **AND** existing values SHALL remain in ff_values but become inaccessible for new assignments

## MODIFIED Requirements
### Requirement: Validation Rule Enforcement
The system SHALL validate flexy field values against their SetField validation rules before saving, only for fields in the assigned set.

#### Scenario: Values are validated when SetField exists
- **WHEN** a flexy field has a defined SetField in the assigned set
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

## MODIFIED Requirements
### Requirement: Set Enforcement
The system SHALL enforce that only fields from the assigned set can be set.

#### Scenario: Field not in set is rejected
- **WHEN** a model has an assigned set_code
- **AND** a flexy field is set that is not in the assigned set
- **THEN** FieldNotInSetException SHALL be thrown
- **AND** the field SHALL NOT be saved
- **AND** the exception message SHALL include the field name, set code, and available fields

#### Scenario: Set assignment is required
- **WHEN** a model attempts to set a flexy field
- **AND** the model has no set_code assigned
- **THEN** SetNotFoundException SHALL be thrown
- **AND** the field SHALL NOT be saved

## MODIFIED Requirements
### Requirement: Set Field Enforcement
The system SHALL enforce that only fields defined in the instance's set can be modified.

#### Scenario: Field not in assigned set is rejected
- **WHEN** a model instance is assigned to a set
- **AND** a flexy field is set that is not defined in the set
- **THEN** FieldNotInSetException SHALL be thrown
- **AND** the field SHALL NOT be saved

#### Scenario: Unassigned instance cannot set fields
- **WHEN** a model instance has no set_code assigned
- **AND** a flexy field is set
- **THEN** SetNotFoundException SHALL be thrown
- **AND** the field SHALL NOT be saved

