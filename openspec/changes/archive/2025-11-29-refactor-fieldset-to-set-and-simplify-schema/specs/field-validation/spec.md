## RENAMED Requirements
- FROM: `### Requirement: Field Set Definition`
- TO: `### Requirement: Schema Definition`
- FROM: `### Requirement: Field Set Enforcement`
- TO: `### Requirement: Schema Enforcement`
- FROM: `### Requirement: Field Set Field Enforcement`
- TO: `### Requirement: Schema Field Enforcement`

## MODIFIED Requirements
### Requirement: Schema Definition
The system SHALL allow defining fields within schemas that specify type, validation rules, and sort order.

#### Scenario: Field is defined with validation rules in schema
- **WHEN** addFieldToSchema() is called with validation_rules
- **THEN** a SchemaField record SHALL be created in ff_schema_fields table
- **AND** the field SHALL include name, type, sort, validation_rules, and validation_messages
- **AND** the field SHALL be scoped to the specified schema_code

#### Scenario: Field type matches FlexyFieldType enum
- **WHEN** a field is added to a schema with a type
- **THEN** the type SHALL be one of the FlexyFieldType enum values
- **AND** the type SHALL be stored in the ff_schema_fields table

#### Scenario: Fields can be removed from schemas
- **WHEN** removeFieldFromSchema() is called for a field
- **THEN** the corresponding SchemaField record SHALL be removed from ff_schema_fields
- **AND** existing values SHALL remain in ff_field_values but become inaccessible for new assignments

## MODIFIED Requirements
### Requirement: Validation Rule Enforcement
The system SHALL validate flexy field values against their SchemaField validation rules before saving, only for fields in the assigned schema.

#### Scenario: Values are validated when SchemaField exists
- **WHEN** a flexy field has a defined SchemaField in the assigned schema
- **AND** the field value is modified
- **THEN** Laravel validation SHALL be performed using the SchemaField's validation_rules
- **AND** the field value SHALL be validated before being saved

#### Scenario: Invalid values are rejected
- **WHEN** a flexy field value fails validation
- **THEN** a ValidationException SHALL be thrown
- **AND** the model save operation SHALL be aborted
- **AND** no changes SHALL be persisted to ff_field_values

#### Scenario: Valid values pass validation
- **WHEN** a flexy field value passes validation
- **THEN** no exception SHALL be thrown
- **AND** the value SHALL be persisted to ff_field_values

## MODIFIED Requirements
### Requirement: Schema Enforcement
The system SHALL enforce that only fields from the assigned schema can be set.

#### Scenario: Field not in schema is rejected
- **WHEN** a model has an assigned schema_code
- **AND** a flexy field is set that is not in the assigned schema
- **THEN** FieldNotInSchemaException SHALL be thrown
- **AND** the field SHALL NOT be saved
- **AND** the exception message SHALL include the field name, schema code, and available fields

#### Scenario: Schema assignment is required
- **WHEN** a model attempts to set a flexy field
- **AND** the model has no schema_code assigned
- **THEN** SchemaNotFoundException SHALL be thrown
- **AND** the field SHALL NOT be saved

## MODIFIED Requirements
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
